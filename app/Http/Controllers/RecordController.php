<?php

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Enums\AutoBudgetType;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Events\Model\TransactionGroup\TransactionGroupEventFlags;
use FireflyIII\Events\Model\TransactionGroup\TransactionGroupEventObjects;
use FireflyIII\Events\Model\TransactionGroup\UpdatedSingleTransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Services\Internal\Update\JournalUpdateService;
use FireflyIII\Support\Facades\Amount;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Record: Jerrold Mundis's Spending Record and Spending Plan, added to
 * Firefly III (see RECORD.md).
 *
 * The numbers are worked out outside Firefly by a script that writes
 * data/record.json (now) and data/months/YYYY-MM.json (any other month, on
 * request). This page only reads them. The same routes answer under
 * api/v1/record/ for the phone app (Shefa, built on Waterfly III), with
 * its API token instead of the web login. It keeps the record_state preference
 * (the day the count starts from, the days marked as looked, the Ideal
 * Spending Plan, what the offer check measures against), which other clients
 * can read and write through Firefly's preferences API, and it writes two things
 * to Firefly itself: the month's Spending Plan, as the budget limits that
 * Firefly's own Budgets page edits, and, from the Sheet, one charge's category,
 * budget or notes, the way Firefly's own transaction editor does.
 */
class RecordController extends Controller
{
    private const string DATA   = '/var/www/record/data/record.json';
    private const string MONTHS = '/var/www/record/data/months/';
    private const string RUN    = '/var/www/record/run/';
    private const string DAY    = '/^\d{4}-\d{2}-\d{2}$/';
    private const string MONTH  = '/^\d{4}-(0[1-9]|1[0-2])$/';

    public function __construct()
    {
        parent::__construct();

        $this->middleware(static function ($request, $next) {
            app('view')->share('title', 'Record');
            app('view')->share('mainTitleIcon', 'bi-journal-check');

            return $next($request);
        });
    }

    public function index(): View
    {
        $subTitle = now()->format('F Y');

        return view('record.index', compact('subTitle'));
    }

    /**
     * The same page for the phone app (api/v1/record/app, behind the API
     * token): no header or sidebar, in the app's light or dark (?theme=dark).
     * Its links back into Firefly are the app's to handle.
     */
    public function app(Request $request): View
    {
        $layout = 'record.bare';
        $theme  = 'dark' === $request->query('theme') ? 'dark' : 'light';

        return view('record.index', compact('layout', 'theme'));
    }

    /**
     * Now, or ?month=YYYY-MM for any other month. A copy that is missing or old
     * is asked for by leaving a note in run/ (want, want-YYYY-MM); a systemd
     * path unit on the host works it out in a few seconds and the page asks
     * again. An old copy is still sent meanwhile; a missing one answers 202.
     */
    public function data(Request $request): JsonResponse
    {
        $month = (string) $request->query('month', '');
        if ('' !== $month && (1 !== preg_match(self::MONTH, $month) || $month > now()->format('Y-m'))) {
            return response()->json(['error' => 'bad month'], 422);
        }
        $file    = '' === $month ? self::DATA : self::MONTHS . $month . '.json';
        $note    = '' === $month ? 'want' : 'want-' . $month;
        $maxAge  = '' === $month ? 60 : 600;    // past months rarely change

        $raw  = is_readable($file) ? file_get_contents($file) : false;
        $age  = false === $raw ? null : time() - (int) filemtime($file);
        $data = false === $raw ? null : json_decode($raw, true);

        if (null === $age || $age > $maxAge) {
            $this->ask($note);
        }
        if (!is_array($data)) {
            return response()->json(['pending' => true], '' === $month ? 503 : 202);
        }
        $data['state'] = $this->readState();
        $data['age']   = $age;

        return response()->json($data);
    }

    public function saveState(Request $request): JsonResponse
    {
        $state = $this->readState();
        $look  = (string) $request->input('look', '');
        $since = $request->input('since');

        if ('' !== $look) {
            if (1 !== preg_match(self::DAY, $look)) {
                return response()->json(['error' => 'bad day'], 422);
            }
            $state['looked'] = array_values(array_unique([...$state['looked'], $look]));
            sort($state['looked']);
            $state['looked'] = array_slice($state['looked'], -400);
        }
        if (null !== $since) {
            $since = (string) $since;
            if ('' !== $since && 1 !== preg_match(self::DAY, $since)) {
                return response()->json(['error' => 'bad day'], 422);
            }
            $state['solvent_since'] = $since;
        }
        if ($request->has('ideal')) {
            $ideal = $this->cleanIdeal($request->input('ideal'));
            if (null === $ideal) {
                return response()->json(['error' => 'bad ideal'], 422);
            }
            $state['ideal'] = $ideal;
        }
        if ($request->has('offer')) {
            $offer = $this->cleanOffer($request->input('offer'));
            if (null === $offer) {
                return response()->json(['error' => 'bad offer'], 422);
            }
            $state['offer'] = $offer;
        }
        app('preferences')->set('record_state', $state);

        return response()->json(['state' => $state]);
    }

    /**
     * The Spending Plan for this month or next (ch. 10): {month: YYYY-MM,
     * lines: {budget id: amount}}. Everything is checked before anything is
     * written. Firefly refuses a limit of 0, so "nothing planned" is kept as
     * 0.01, which the Record reads as 0.
     *
     * A line with an auto-budget gets the amount as its auto-budget amount,
     * the base that repeats each month. Next month's limit is then left for
     * Firefly's own auto-budget job to make on the 1st (03:00, firefly-cron),
     * because a rollover line only carries its leftover into a month that has
     * no limit yet; one already there for next month is removed for the same
     * reason. This month's limit becomes the new base plus whatever Firefly
     * carried in on the 1st. A line without an auto-budget just gets the
     * month's limit.
     */
    public function savePlan(Request $request): JsonResponse
    {
        $month = (string) $request->input('month', '');
        if (!in_array($month, [now()->format('Y-m'), now()->startOfMonth()->addMonth()->format('Y-m')], true)) {
            return response()->json(['error' => 'bad month'], 422);
        }
        $lines = $request->input('lines');
        if (!is_array($lines) || [] === $lines || count($lines) > 200) {
            return response()->json(['error' => 'no lines'], 422);
        }
        $user = auth()->user();
        $todo = [];
        foreach ($lines as $id => $amount) {
            $budget = $user->budgets()->find((int) $id);
            if (null === $budget || !is_numeric($amount) || $amount < 0 || $amount > 1_000_000) {
                return response()->json(['error' => 'bad line'], 422);
            }
            $todo[] = [$budget, number_format(max(0.01, round((float) $amount, 2)), 2, '.', '')];
        }

        $start    = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay();
        $end      = $start->copy()->endOfMonth()->startOfDay();
        $currency = Amount::getPrimaryCurrencyByUserGroup($user->userGroup);
        $repos    = app(BudgetLimitRepositoryInterface::class);
        $repos->setUser($user);
        $changed  = 0;
        $isNow    = $month === now()->format('Y-m');
        foreach ($todo as [$budget, $amount]) {
            $limit = $repos->find($budget, $currency, $start, $end);
            $auto  = $budget->autoBudgets()->first();
            if (null !== $auto) {
                $base = (string) $auto->amount;
                if (0 !== bccomp($base, $amount, 2)) {
                    $auto->amount = $amount;
                    $auto->save();
                    ++$changed;
                }
                if (!$isNow) {
                    if (null !== $limit) {
                        $repos->destroyBudgetLimit($limit);
                        ++$changed;
                    }

                    continue;
                }
                $carried = AutoBudgetType::AUTO_BUDGET_ROLLOVER->value === (int) $auto->auto_budget_type && null !== $limit
                    ? max(0, (float) bcsub((string) $limit->amount, $base, 2)) : 0;
                $amount  = number_format((float) $amount + $carried, 2, '.', '');
            }
            if (null === $limit && '0.01' !== $amount) {
                $repos->store(['budget_id' => $budget->id, 'currency_id' => $currency->id, 'start_date' => $start,
                    'end_date' => $end, 'amount' => $amount, 'period' => 'monthly']);
                ++$changed;
            }
            if (null !== $limit && 0 !== bccomp((string) $limit->amount, $amount, 2)) {
                $repos->update($limit, ['amount' => $amount]);
                ++$changed;
            }
        }
        if ($changed > 0) {
            $this->ask('want');
        }

        return response()->json(['changed' => $changed]);
    }

    /**
     * One charge changed from the Sheet: {id: journal id, and any of category
     * (a name; a new one is made, '' takes it off), line (a budget id; '' for
     * none), note and was (the note as the Sheet showed it, which is replaced,
     * so any other text in the notes stays)}. Only spending can be changed.
     *
     * The change goes through Firefly's own journal update, one split at a
     * time, without running the rules. Afterwards the page's numbers are asked
     * for again, and a note want-tx-<id> tells the host which charge changed, in
     * case it keeps hand-made changes of its own (a pending charge is replaced
     * by a new one when it settles).
     */
    public function saveTx(Request $request): JsonResponse
    {
        $user    = auth()->user();
        $journal = $user->transactionJournals()->find((int) $request->input('id'));
        if (!$journal instanceof TransactionJournal
            || TransactionTypeEnum::WITHDRAWAL->value !== $journal->transactionType->type) {
            return response()->json(['error' => 'no such charge'], 404);
        }
        $data    = [];
        if ($request->has('category')) {
            $name = trim((string) $request->input('category'));
            if (mb_strlen($name) > 100 || 1 === preg_match('/[\x00-\x1f]/', $name)) {
                return response()->json(['error' => 'bad category'], 422);
            }
            $data['category_name'] = $name;
            $data['category_id']   = null;
        }
        if ($request->has('line')) {
            $line = (string) $request->input('line');
            if ('' !== $line && null === $user->budgets()->find((int) $line)) {
                return response()->json(['error' => 'bad line'], 422);
            }
            $data['budget_id'] = '' === $line ? null : (int) $line;
        }
        if ($request->has('note')) {
            $note = trim((string) $request->input('note'));
            $was  = trim((string) $request->input('was', ''));
            $text = (string) ($journal->notes()->first()?->text ?? '');
            if (mb_strlen($note) > 2000) {
                return response()->json(['error' => 'note too long'], 422);
            }
            if ('' !== $was) {
                $at = mb_strpos($text, $was);
                if (false === $at) {
                    return response()->json(['error' => 'the note changed meanwhile'], 409);
                }
                $text = mb_substr($text, 0, $at) . $note . mb_substr($text, $at + mb_strlen($was));
            } else {
                $text .= "\n" . $note;
            }
            $data['notes'] = trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
        }
        if ([] === $data) {
            return response()->json(['error' => 'nothing to change'], 422);
        }

        $group   = $journal->transactionGroup;
        $objects = TransactionGroupEventObjects::collectFromTransactionGroup($group);
        $service = app(JournalUpdateService::class);
        $service->setTransactionGroup($group);
        $service->setTransactionJournal($journal);
        $service->setData($data);
        $service->update();
        $group->refresh();
        $objects->appendFromTransactionGroup($group);
        $flags                    = new TransactionGroupEventFlags();
        $flags->applyRules        = false;
        $flags->fireWebhooks      = false;
        $flags->recalculateCredit = false;
        event(new UpdatedSingleTransactionGroup($flags, $objects));

        $month = $journal->date->format('Y-m');
        $this->ask('want');
        if ($month < now()->startOfMonth()->subMonth()->format('Y-m')) {
            $this->ask('want-' . $month);
        }
        $fields = array_values(array_intersect(['category', 'line', 'note'], array_keys($request->all())));
        @file_put_contents(self::RUN . 'want-tx-' . $journal->id, json_encode(['fields' => $fields]));

        return response()->json(['ok' => true]);
    }

    private function readState(): array
    {
        $data = app('preferences')->get('record_state', ['solvent_since' => '', 'looked' => []])?->data;
        $data = is_array($data) ? $data : [];

        return [
            'solvent_since' => (string) ($data['solvent_since'] ?? ''),
            'looked'        => array_values(array_filter((array) ($data['looked'] ?? []), 'is_string')),
            'ideal'         => $this->cleanIdeal($data['ideal'] ?? []) ?? [],
            'offer'         => $this->cleanOffer($data['offer'] ?? []) ?? [],
        ];
    }

    /**
     * What the offer check measures against (Earn What You Deserve, ch. 3):
     * {hours: hours a month to be worked, tax: percent set aside for tax};
     * either may be left out. Null when malformed.
     */
    private function cleanOffer(mixed $offer): ?array
    {
        if (!is_array($offer)) {
            return null;
        }
        $out = [];
        foreach (['hours' => 744, 'tax' => 90] as $key => $max) {
            $v = $offer[$key] ?? null;
            if (null === $v || '' === $v) {
                continue;
            }
            if (!is_numeric($v) || $v < 0 || $v > $max) {
                return null;
            }
            $out[$key] = round((float) $v, 1);
        }

        return $out;
    }

    /** The Ideal Spending Plan (ch. 20): {line name: amount}; null when malformed. */
    private function cleanIdeal(mixed $ideal): ?array
    {
        if (!is_array($ideal) || count($ideal) > 200) {
            return null;
        }
        $out = [];
        foreach ($ideal as $name => $amount) {
            if (!is_string($name) || '' === $name || mb_strlen($name) > 100) {
                return null;
            }
            if (null === $amount || '' === $amount) {
                continue;
            }
            if (!is_numeric($amount) || $amount < 0 || $amount > 1_000_000) {
                return null;
            }
            $out[$name] = round((float) $amount, 2);
        }

        return $out;
    }

    private function ask(string $note): void
    {
        if (!file_exists(self::RUN . $note)) {
            @touch(self::RUN . $note);
        }
    }
}
