@extends($layout ?? 'layout.v3.session')
{{-- The Record page (see RECORD.md). The numbers come from record/data; the
     page draws them the way Jerrold Mundis's workbook does: weeks by calendar
     date, Actual, Plan, (+) or (−). Its Sheet shows every charge as a row whose
     category, line and note can be changed (record/tx). The phone app shows the
     same page in record/bare, from api/v1/record/app. --}}
@section('breadcrumbs')
    <ol class="breadcrumb float-sm-end">
        <li class="breadcrumb-item"><a href="{{ route('index') }}">{{ __('breadcrumbs.home') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Record</li>
    </ol>
@endsection
@section('content')
    @verbatim
    <style>
  .rec-week { display: grid; grid-template-columns: 3.4rem repeat(10, minmax(0, 1fr)) 4.4rem; gap: 4px; margin-bottom: 4px; }
  .rec-wl { font-size: .75rem; color: var(--bs-secondary-color); align-self: center; }
  .rec-wt { font-size: .85rem; text-align: right; align-self: center; font-variant-numeric: tabular-nums; }
  .rec-day { position: relative; border: 1px solid var(--bs-border-color); border-radius: .375rem; background: var(--bs-tertiary-bg);
    min-height: 3.1rem; padding: 3px 5px; display: flex; flex-direction: column; justify-content: space-between;
    font-size: .72rem; line-height: 1.15; color: var(--bs-body-color); text-align: left; cursor: pointer; }
  .rec-day b { font-weight: 500; white-space: nowrap; }
  .rec-day span { font-variant-numeric: tabular-nums; white-space: nowrap; overflow: hidden; }
  .rec-day.s-green { background: var(--bs-success-bg-subtle); border-color: var(--bs-success-border-subtle); color: var(--bs-success-text-emphasis); }
  .rec-day.s-amber { background: var(--bs-warning-bg-subtle); border-color: var(--bs-warning-border-subtle); color: var(--bs-warning-text-emphasis); }
  .rec-day.s-red { background: var(--bs-danger-bg-subtle); border-color: var(--bs-danger-border-subtle); color: var(--bs-danger-text-emphasis); }
  .rec-day.s-unplanned { background: var(--bs-secondary-bg); }
  .rec-day.s-future { background: transparent; border-style: dashed; color: var(--bs-secondary-color); cursor: default; }
  .rec-day.today { border-width: 2px; border-color: var(--bs-primary); }
  .rec-day.sel { outline: 2px solid var(--bs-emphasis-color); outline-offset: 1px; }
  .rec-day.looked::after { content: '✓'; position: absolute; top: 2px; right: 4px; font-size: .7rem; }
  .rec-key { display: inline-flex; align-items: center; gap: .35rem; margin-right: .9rem; font-size: .8rem; }
  .rec-key i { width: .8rem; height: .8rem; border-radius: 3px; border: 1px solid; display: inline-block; }
  .rec-key .k-green { background: var(--bs-success-bg-subtle); border-color: var(--bs-success-border-subtle); }
  .rec-key .k-amber { background: var(--bs-warning-bg-subtle); border-color: var(--bs-warning-border-subtle); }
  .rec-key .k-red { background: var(--bs-danger-bg-subtle); border-color: var(--bs-danger-border-subtle); }
  table.rec td, table.rec th { font-variant-numeric: tabular-nums; }
  table.rec .grp td { background: var(--bs-tertiary-bg); font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: var(--bs-secondary-color); font-weight: 500; }
  table.rec .tot td { font-weight: 600; border-top: 2px solid var(--bs-border-color); }
  table.rec a { text-decoration: none; }
  table.rec a:hover { text-decoration: underline; }
  .small-box .rec-date { max-width: 11rem; }
  .small-box-footer button { background: none; border: 0; color: inherit; padding: 0; }
  .rec-sub { font-size: .78rem; color: var(--bs-secondary-color); }
  .rec-sub a { color: inherit; }
  .rec-kv td:last-child { text-align: right; font-variant-numeric: tabular-nums; }
  .rec-h { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: var(--bs-secondary-color); margin: .9rem 0 .3rem; font-weight: 500; }
  .rec-h:first-child { margin-top: 0; }
  table.rec input.form-control { max-width: 6.5rem; margin-left: auto; text-align: right; }
  details.rec-red { border-bottom: 1px solid var(--bs-border-color-translucent); padding: .3rem 0; }
  details.rec-red summary { display: flex; align-items: baseline; gap: .5rem; cursor: pointer; list-style: none; }
  details.rec-red summary::-webkit-details-marker { display: none; }
  details.rec-red summary .amt { margin-left: auto; font-variant-numeric: tabular-nums; }
  details.rec-red table { font-size: .8rem; margin: .3rem 0 0; }
  details.rec-red td { font-variant-numeric: tabular-nums; }
  /* Earnings: side by side only when the card itself is wide enough, whatever the window */
  .rec-earn { container: earn / inline-size; }
  .rec-earn-grid, .rec-earn-side { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; }
  @container earn (min-width: 44rem) { .rec-earn-side { grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr); } }
  @container earn (min-width: 62rem) {
    .rec-earn-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
    .rec-earn-side { grid-template-columns: minmax(0, 1fr); }
  }
  @media (max-width: 575.98px) {
    .rec-week { grid-template-columns: 2.5rem repeat(10, minmax(0, 1fr)) 3.2rem; gap: 3px; }
    .rec-day { min-height: 2.2rem; padding: 0; justify-content: center; align-items: center; font-size: .66rem; }
    .rec-day span { display: none; }
    .rec-wl, .rec-wt { font-size: .7rem; }
    .rec-day.looked::after { top: auto; bottom: 1px; right: auto; left: 50%; transform: translateX(-50%); font-size: .5rem; }
  }
  /* the Sheet */
  .sh-card { overflow: hidden; }
  .sh-wrap { overflow: auto; max-height: calc(100vh - 15rem); min-height: 18rem; }
  table.sh { border-collapse: separate; border-spacing: 0; width: 100%; font-size: .85rem; }
  table.sh th, table.sh td { border-right: 1px solid var(--bs-border-color); border-bottom: 1px solid var(--bs-border-color);
    padding: .28rem .5rem; white-space: nowrap; vertical-align: middle; }
  table.sh thead th { position: sticky; top: 0; z-index: 2; background: var(--bs-tertiary-bg); font-weight: 500;
    font-size: .75rem; color: var(--bs-secondary-color); user-select: none; }
  table.sh thead th[data-sh-sort] { cursor: pointer; }
  table.sh thead th .bi { font-size: .65rem; margin-left: .2rem; }
  table.sh .sh-rn { position: sticky; left: 0; z-index: 1; background: var(--bs-tertiary-bg); color: var(--bs-secondary-color);
    font-size: .7rem; text-align: right; width: 2.4rem; }
  table.sh thead .sh-rn { z-index: 3; }
  .sh-num { text-align: right; font-variant-numeric: tabular-nums; }
  table.sh td.sh-dt { color: var(--bs-secondary-color); font-size: .8rem; }
  table.sh td.sh-dt.sh-rep { color: transparent; }
  table.sh td.sh-what { max-width: 24rem; overflow: hidden; text-overflow: ellipsis; }
  table.sh td.sh-what a { color: var(--bs-body-color); text-decoration: none; }
  table.sh td.sh-what a:hover { text-decoration: underline; }
  table.sh td.sh-what small, .sh-native { color: var(--bs-secondary-color); font-size: .75rem; }
  table.sh td.sh-cell { cursor: pointer; position: relative; min-width: 9.5rem; }
  table.sh td.sh-comment { min-width: 12rem; white-space: normal; }
  table.sh td.sh-note { min-width: 12rem; max-width: 22rem; white-space: normal; color: var(--bs-secondary-color); font-size: .78rem; }
  table.sh td.sh-active { box-shadow: inset 0 0 0 2px var(--bs-primary); }
  @media (hover: hover) { table.sh td.sh-cell:hover { box-shadow: inset 0 0 0 2px var(--bs-primary); } }
  .sh-cv { display: flex; align-items: center; }
  .sh-caret { margin-left: auto; padding-left: .5rem; opacity: .3; font-size: .65rem; }
  .sh-edited { position: relative; }
  .sh-edited::after { content: ''; position: absolute; top: 0; right: 0; border-style: solid; border-width: 0 7px 7px 0;
    border-color: transparent var(--bs-success) transparent transparent; }
  .sh-saving::after { border-right-color: var(--bs-secondary-color); }
  .sh-failed { box-shadow: inset 0 0 0 2px var(--bs-danger) !important; }
  .sh-chip.sh-edited::after { top: 2px; right: 6px; }
  table.sh tr.sh-pending td.sh-amt { font-style: italic; color: var(--bs-secondary-color); }
  .sh-sw { display: inline-block; width: .6rem; height: .6rem; border-radius: 2px; margin-right: .45rem; flex: none; }
  .sh-g0 { background: var(--bs-blue); }
  .sh-g1 { background: var(--bs-purple); }
  .sh-g2 { background: var(--bs-teal); }
  .sh-g3 { background: var(--bs-cyan); }
  .sh-sw.sh-out { background: var(--bs-orange); }
  .sh-quiet { background: var(--bs-gray-500); }
  .sh-none { border: 1px dashed var(--bs-secondary-color); }
  .sh-bad { background: var(--bs-danger-bg-subtle) !important; color: var(--bs-danger-text-emphasis) !important; }
  td.sh-out, .sh-chip.sh-out { background: var(--bs-warning-bg-subtle) !important; color: var(--bs-warning-text-emphasis) !important; }
  table.sh tr.sh-dayrow td { background: var(--bs-body-bg); font-weight: 500; }
  table.sh tr.sh-dayrow td.sh-rn { background: var(--bs-tertiary-bg); }
  table.sh tr.sh-dayrow.sh-s-green td:nth-child(2) { box-shadow: inset 3px 0 0 var(--bs-success); }
  table.sh tr.sh-dayrow.sh-s-amber td:nth-child(2) { box-shadow: inset 3px 0 0 var(--bs-warning); }
  table.sh tr.sh-dayrow.sh-s-red td:nth-child(2) { box-shadow: inset 3px 0 0 var(--bs-danger); }
  .sh-crossed { font-weight: 400; font-size: .78rem; margin-left: .6rem; }
  table.sh tr.sh-weekrow td { background: var(--bs-tertiary-bg); font-weight: 600; font-size: .8rem; }
  table.sh tr.sh-empty td { text-align: center; color: var(--bs-secondary-color); padding: 2.5rem; }
  .sh-pill { display: inline-block; border-radius: .3rem; padding: .05rem .45rem; border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg); font-variant-numeric: tabular-nums; }
  .sh-pill.sh-s-green { background: var(--bs-success-bg-subtle); border-color: var(--bs-success-border-subtle); color: var(--bs-success-text-emphasis); }
  .sh-pill.sh-s-amber { background: var(--bs-warning-bg-subtle); border-color: var(--bs-warning-border-subtle); color: var(--bs-warning-text-emphasis); }
  .sh-pill.sh-s-red { background: var(--bs-danger-bg-subtle); border-color: var(--bs-danger-border-subtle); color: var(--bs-danger-text-emphasis); }
  table.sh-lines td.sh-wk, table.sh-lines td.sh-name { cursor: pointer; }
  @media (hover: hover) { table.sh-lines td.sh-wk:hover { box-shadow: inset 0 0 0 2px var(--bs-primary); } }
  table.sh-lines tr.sh-grp td { background: var(--bs-tertiary-bg); font-size: .72rem; text-transform: uppercase; letter-spacing: .04em;
    color: var(--bs-secondary-color); font-weight: 500; }
  table.sh-lines tr.sh-tot td { font-weight: 600; border-top: 2px solid var(--bs-border-color); }
  .sh-flash { animation: sh-flash 1.4s ease-out; }
  @keyframes sh-flash { from { background: var(--bs-success-bg-subtle); } to { background: transparent; } }
  .sh-foot { display: flex; align-items: stretch; border-top: 1px solid var(--bs-border-color); background: var(--bs-tertiary-bg); font-size: .8rem; }
  .sh-tabs { display: flex; overflow-x: auto; scrollbar-width: none; }
  .sh-tabs button { border: 0; border-right: 1px solid var(--bs-border-color); background: transparent; padding: .45rem .95rem;
    color: var(--bs-secondary-color); white-space: nowrap; }
  .sh-tabs button.sh-on { background: var(--bs-body-bg); color: var(--bs-body-color); font-weight: 500; box-shadow: inset 0 -2px 0 var(--bs-success); }
  .sh-tabs button.sh-today::after { content: ''; display: inline-block; width: .35rem; height: .35rem; border-radius: 50%;
    background: var(--bs-primary); margin-left: .35rem; vertical-align: middle; }
  .sh-sum { margin-left: auto; padding: .45rem .95rem; white-space: nowrap; color: var(--bs-secondary-color); }
  .sh-sum b { color: var(--bs-body-color); font-weight: 500; font-variant-numeric: tabular-nums; margin-left: .25rem; }
  .sh-q { max-width: 13rem; }
  .sh-pk { position: fixed; z-index: 1060; width: 18rem; max-height: 24rem; display: flex; flex-direction: column;
    background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: .5rem; box-shadow: var(--bs-box-shadow); }
  .sh-pk input, .sh-pk textarea { margin: .5rem; width: auto; }
  .sh-pk textarea { min-height: 5rem; }
  .sh-pk-list { overflow: auto; padding-bottom: .4rem; }
  .sh-pk-h { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: var(--bs-secondary-color); padding: .45rem .75rem .15rem; }
  .sh-pk-list button { display: flex; width: 100%; align-items: center; border: 0; background: none; text-align: left;
    padding: .3rem .75rem; font-size: .875rem; color: var(--bs-body-color); }
  .sh-pk-list button.sh-hl { background: var(--bs-primary-bg-subtle); }
  @media (hover: hover) { .sh-pk-list button:hover { background: var(--bs-primary-bg-subtle); } }
  .sh-pk-list button.sh-cur { font-weight: 600; }
  .sh-r { margin-left: auto; padding-left: .6rem; font-size: .75rem; font-variant-numeric: tabular-nums; }
  .sh-pk-back { position: fixed; inset: 0; z-index: 1055; }
  .sh-pk-btns { display: flex; justify-content: flex-end; gap: .4rem; padding: 0 .5rem .5rem; }
  .sh-m-day { display: flex; justify-content: space-between; align-items: center; padding: .45rem .75rem; background: var(--bs-tertiary-bg);
    font-weight: 500; font-size: .85rem; position: sticky; top: 0; z-index: 1; border-bottom: 1px solid var(--bs-border-color); }
  .sh-m-week { padding: .45rem .75rem; font-weight: 600; font-size: .8rem; display: flex; justify-content: space-between; border-bottom: 1px solid var(--bs-border-color); }
  .sh-m-row { padding: .5rem .75rem; border-bottom: 1px solid var(--bs-border-color); }
  .sh-m-top { display: flex; gap: .5rem; align-items: baseline; }
  .sh-m-what { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--bs-body-color); text-decoration: none; }
  .sh-m-acct { font-size: .75rem; color: var(--bs-secondary-color); white-space: nowrap; }
  .sh-m-amt { font-variant-numeric: tabular-nums; font-weight: 500; }
  .sh-m-row.sh-pending .sh-m-amt { font-style: italic; color: var(--bs-secondary-color); font-weight: 400; }
  .sh-m-bot { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .35rem; align-items: center; }
  .sh-chip { position: relative; display: inline-flex; align-items: center; border: 1px solid var(--bs-border-color); background: var(--bs-body-bg);
    border-radius: 1rem; padding: .12rem .65rem; font-size: .78rem; color: var(--bs-body-color); }
  .sh-chip.sh-bad { border-color: var(--bs-danger-border-subtle); }
  .sh-chip.sh-out { border-color: var(--bs-warning-border-subtle); }
  .sh-m-comment { font-size: .85rem; margin-top: .3rem; }
  .sh-m-note { font-size: .78rem; color: var(--bs-secondary-color); margin-top: .2rem; white-space: pre-line; }
  @media (max-width: 575.98px) {
    .sh-wrap { max-height: none; min-height: 0; overflow: visible; }
    .sh-wrap.sh-lines-wrap { overflow-x: auto; }
    .sh-card { overflow: visible; }
    .sh-foot { position: sticky; bottom: 0; z-index: 4; flex-wrap: wrap; }
    .sh-foot .sh-sum { order: -1; flex: 1 1 100%; text-align: right; padding: .25rem .75rem; border-bottom: 1px solid var(--bs-border-color); }
    .sh-tabs button { padding: .5rem .8rem; }
    .sh-pk { left: 0 !important; right: 0; bottom: 0; top: auto !important; width: auto; max-height: 72vh; border-radius: .9rem .9rem 0 0; padding-bottom: .5rem; }
    .sh-pk-back { background: rgba(0, 0, 0, .35); }
    .sh-pk-list button { padding: .5rem 1rem; font-size: .95rem; }
    .sh-q { max-width: none; flex: 1 1 100%; }
  }
    </style>
    @endverbatim
    <div id="record-page"><div class="text-center text-body-secondary py-5"><span class="spinner-border spinner-border-sm"></span></div></div>
@endsection
@section('scripts')
<script nonce="{{ $JS_NONCE }}">
@verbatim
'use strict';
(() => {
let R = null;               // record/data
let view = null;            // the month shown, YYYY-MM
const cache = {};           // YYYY-MM → that month's record
const limits = {};          // YYYY-MM → Firefly budget-limit ids, for the line links
let asking = null;          // the month being fetched
let sel = null;             // the day open in the day card
let pay = 0;                // the monthly repayment being tried
let planning = null;        // YYYY-MM while the Spending Plan is being made
let drawn = null;           // the month whose plan boxes are on screen, so a redraw keeps what was typed
const offerIn = { amt: '', hrs: '', via: '' };   // the offer being weighed; kept across redraws
let sheet = (() => { try { return localStorage.getItem('record.view') === 'sheet'; } catch (e) { return false; } })();
let freshening = false;     // a fresh copy is being waited for
let freshAgain = false;     // and another is wanted once it lands (a change made meanwhile)

const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
// In the phone app the page came from api/v1/record/app, with the app's token
// after the #, and everything it asks for goes through the API with that token.
const inApp = document.documentElement.dataset.recordApp === '1';
const auth = inApp ? new URLSearchParams(location.hash.slice(1)).get('auth') : null;
const base = inApp ? 'api/v1/record/' : 'record/';
const here = inApp ? 'api/v1/record/app' : 'record';
if (auth) history.replaceState(null, '', location.pathname + location.search);
const isLooked = d => R.state.looked.includes(d);

const pad2 = n => String(n).padStart(2, '0');
const num = (n, d = 2) => Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
const usd = n => '$' + num(n, 2);
const usd0 = n => '$' + num(Math.round(n || 0), 0);
const sign = n => (n > 0.004 ? '+' : n < -0.004 ? '−' : '') + num(Math.abs(n));
const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
// links are relative to Firefly's <base href>, so they work on every address Firefly answers on
const link = (path, html, cls = '') => `<a href="${esc(path.replace(/^\//, ''))}"${cls ? ` class="${cls}"` : ''}>${html}</a>`;
const search = s => '/search?search=' + encodeURIComponent(s);
const daysBetween = (a, b) => Math.round((new Date(b + 'T12:00') - new Date(a + 'T12:00')) / 86400000);
const fmtDay = (d, o) => new Date(d + 'T12:00').toLocaleDateString('en-US', o);

const mStart = m => m.key + '-01';
const mEnd = m => m.key + '-' + pad2(m.days_in_month);
const wStart = (m, w) => m.key + '-' + pad2(w.start);
const wEnd = (m, w) => m.key + '-' + pad2(w.end);
const budgetHref = (m, name) => {
  const b = R.ids.budgets[name], l = (limits[m.key] || {})[b];
  return b ? `/budgets/show/${b}${l ? '/' + l : ''}` : null;
};
const catHref = (m, name) => R.ids.categories[name] ? `/categories/show/${R.ids.categories[name]}/${mStart(m)}/${mEnd(m)}` : null;
const acctId = name => R.ids.accounts[name] || R.ids.accounts[Object.keys(R.ids.accounts).find(k => k.startsWith(name + ' ('))];
const acctHref = name => acctId(name) ? `/accounts/show/${acctId(name)}` : null;
const maybe = (href, html, cls) => href ? link(href, html, cls) : html;

/* workbook colours: left over green, exactly spent amber, past the plan red */
function diffCls(l) {
  if (l.name === 'Savings') return l.actual + 0.004 >= l.plan ? 'text-success' : '';
  if (l.group === 'Set aside') return '';
  return l.diff > 0.004 ? 'text-success' : l.diff < -0.004 ? 'text-danger' : 'text-warning';
}

function boxes(m) {
  const cur = R.months[0], today = R.today, since = R.state.solvent_since;
  const td = cur.days.find(d => d.date === today) || { total: 0, items: [], week: 0 };
  const w = cur.weeks[td.week] || cur.weeks[0];
  const box = (bg, icon, h, p, foot) => `<div class="col-6 col-xl-3"><div class="small-box ${bg}">
      <div class="inner"><h4>${h}</h4><p class="mb-0">${p}</p></div>
      <span class="small-box-icon d-none d-lg-block"><em class="bi ${icon}"></em></span>
      <div class="small-box-footer">${foot}</div></div></div>`;
  const lookedToday = isLooked(today);
  return `<div class="row">
    ${box('text-bg-indigo', 'bi-calendar-check', usd(td.total), `Today · ${td.items.length} charge${td.items.length === 1 ? '' : 's'}`,
      lookedToday ? 'Looked ✓' : `<button data-look="${today}">Mark looked <em class="bi bi-check2"></em></button>`)}
    ${box('text-bg-sky', 'bi-calendar-week', usd0(cur.totals.weeks[td.week] || 0), `Week ${w.n} · ${fmtDay(today, { month: 'short' })} ${esc(w.label)}`,
      link(`/transactions/withdrawal/${wStart(cur, w)}/${wEnd(cur, w)}`, 'Transactions <em class="bi bi-arrow-right-circle"></em>'))}
    ${box('text-bg-teal', 'bi-pie-chart', `${usd0(cur.totals.spent)} <small>of ${usd0(cur.totals.plan)}</small>`, esc(cur.label.split(' ')[0]),
      link(`/budgets/${mStart(cur)}/${mEnd(cur)}`, 'Budgets <em class="bi bi-arrow-right-circle"></em>'))}
    ${box('text-bg-orange', 'bi-sunrise', since ? `${num(daysBetween(since, today), 0)} <small>days</small>` : '—', 'Just for today',
      `<label class="d-flex flex-wrap justify-content-center align-items-center gap-1 px-2"><span class="text-nowrap">Count from</span><input type="date" id="since" class="form-control form-control-sm rec-date" max="${today}" value="${esc(since)}"></label>`)}
  </div>`;
}

function bar() {
  const feeds = (R.feeds || []).map(f => link(acctHref(f.account) || '/accounts/asset',
    `${esc(f.account)} ${!f.last ? '—' : f.days > 4 ? f.days + ' days' : esc(f.last.slice(5))}`,
    `badge ${f.days > 4 ? 'text-bg-warning' : 'text-bg-secondary'} text-decoration-none`)).join(' ');
  const sig = (R.signals || []).map(s => `<div class="alert alert-warning py-2 mb-2">${esc(s.date)} · ${esc(s.what)}</div>`).join('');
  return `${sig}<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
    ${picker()}
    <div class="btn-group btn-group-sm" role="group">
      <button class="btn ${sheet ? 'btn-outline-secondary' : 'btn-secondary'}" data-sheet="0">Record</button>
      <button class="btn ${sheet ? 'btn-secondary' : 'btn-outline-secondary'}" data-sheet="1">Sheet</button>
    </div>
    <div class="ms-auto d-flex flex-wrap align-items-center gap-1">${updated()} ${feeds}</div>
  </div>`;
}

function daily(m) {
  if (!sel || !m.days.some(d => d.date === sel)) {
    sel = m.days.some(d => d.date === R.today) ? R.today
      : ([...m.days].reverse().find(d => d.total > 0) || m.days[0]).date;
  }
  const rows = m.weeks.map((w, i) => {
    const days = m.days.filter(d => d.week === i);
    const cells = days.map(d => `<button class="rec-day s-${d.status}${d.date === R.today ? ' today' : ''}${d.date === sel ? ' sel' : ''}${isLooked(d.date) ? ' looked' : ''}"
        data-day="${d.date}" ${d.status === 'future' ? 'disabled' : ''}><b>${d.day}</b><span>${d.total ? usd0(d.total) : ''}</span></button>`).join('');
    const padd = '<span></span>'.repeat(Math.max(0, 10 - days.length));
    return `<div class="rec-week"><span class="rec-wl">${esc(w.label)}</span>${cells}${padd}
      <span class="rec-wt">${link(`/transactions/withdrawal/${wStart(m, w)}/${wEnd(m, w)}`, usd0(m.totals.weeks[i] || 0))}</span></div>`;
  }).join('');
  return `<div class="card mb-2">
    <div class="card-header"><h3 class="card-title">Daily record</h3></div>
    <div class="card-body">${rows}</div>
    <div class="card-footer">
      ${m.planned === false ? '' : `<span class="rec-key"><i class="k-green"></i>Inside the plan</span>
      <span class="rec-key"><i class="k-amber"></i>Outside the plan</span>
      <span class="rec-key"><i class="k-red"></i>Past a line</span>`}
      <span class="rec-key">✓ Looked</span>
    </div>
  </div>`;
}

function dayCard(m) {
  const d = m.days.find(x => x.date === sel) || { items: [], total: 0, crossed: [] };
  const rows = d.items.map(it => {
    const where = it.line
      ? maybe(budgetHref(m, it.line), esc(it.line))
      : `<span class="text-warning-emphasis">${maybe(catHref(m, it.category), esc(it.category))}</span>`;
    return `<tr><td>${link(`/transactions/show/${it.id}`, esc(it.what))}
        <div class="rec-sub">${where} · ${maybe(acctHref(it.account), esc(it.account))}${it.pending ? ' <span class="badge text-bg-light border">pending</span>' : ''}</div></td>
      <td class="text-end text-nowrap money-negative">${usd(it.amount)}</td></tr>`;
  }).join('');
  const crossed = d.crossed && d.crossed.length
    ? `<div class="alert alert-danger py-1 px-2 m-2 small">Past plan: ${d.crossed.map(esc).join(', ')}</div>` : '';
  const looked = isLooked(sel);
  return `<div class="card mb-2">
    <div class="card-header d-flex align-items-center">
      <h3 class="card-title mb-0">${esc(fmtDay(sel, { weekday: 'long', month: 'short', day: 'numeric' }))}</h3>
      <div class="ms-auto">${link(`/transactions/withdrawal/${sel}/${sel}`, '<em class="bi bi-box-arrow-up-right"></em>', 'btn btn-sm btn-outline-secondary')}</div>
    </div>
    ${crossed}
    <div class="card-body p-0">${rows
      ? `<table class="table table-sm table-hover mb-0"><tbody>${rows}</tbody></table>`
      : '<p class="text-muted m-3 mb-3">Nothing spent</p>'}</div>
    <div class="card-footer d-flex align-items-center">
      <b>${usd(d.total)}</b>
      <span class="ms-auto">${looked
        ? '<span class="btn btn-sm btn-success disabled">Looked ✓</span>'
        : `<button class="btn btn-sm btn-outline-success" data-look="${sel}">Mark looked</button>`}</span>
    </div>
  </div>`;
}

function table(m) {
  const wk = (vals, q) => vals.map((v, i) => `<td class="text-end d-none d-md-table-cell">${v
    ? (q ? link(search(`${q} date_after:${wStart(m, m.weeks[i])} date_before:${wEnd(m, m.weeks[i])}`), num(v)) : num(v)) : ''}</td>`).join('');
  const planned = m.planned !== false;
  let body = '', group = null;
  m.lines.forEach(l => {
    if (l.group !== group) { group = l.group; body += `<tr class="grp"><td colspan="8">${esc(group)}</td></tr>`; }
    body += `<tr><td>${maybe(budgetHref(m, l.name), esc(l.name))}</td>${wk(l.weeks, `budget_is:"${l.name}"`)}
      <td class="text-end">${num(l.actual)}</td><td class="text-end">${planned ? num(l.plan, 0) : ''}</td>
      <td class="text-end ${planned ? diffCls(l) : ''}">${planned ? sign(l.diff) : ''}</td></tr>`;
  });
  if (m.outside.length) {
    body += `<tr class="grp"><td colspan="8">${planned ? 'Outside the plan' : 'No budget'}</td></tr>` + m.outside.map(o =>
      `<tr><td>${maybe(catHref(m, o.name), esc(o.name))}</td>${wk(o.weeks, `category_is:"${o.name}"`)}
        <td class="text-end">${num(o.actual)}</td><td></td><td></td></tr>`).join('');
  }
  const t = m.totals;
  body += `<tr class="tot"><td>${link(`/transactions/withdrawal/${mStart(m)}/${mEnd(m)}`, 'Total spent')}</td>${wk(t.weeks)}
      <td class="text-end">${num(t.spent)}</td><td class="text-end">${planned ? num(t.plan, 0) : ''}</td>
      <td class="text-end ${t.plan_left >= 0 ? 'text-success' : 'text-danger'}">${planned ? sign(t.plan_left) : ''}</td></tr>
    <tr><td>${link(`/transactions/deposit/${mStart(m)}/${mEnd(m)}`, 'Money in')}</td><td class="d-none d-md-table-cell" colspan="4"></td>
      <td class="text-end">${num(m.income.money_in)}</td><td></td><td class="text-end text-muted small">${usd0(m.income.earned)} earned</td></tr>
    <tr><td>${link(`/transactions/transfers/${mStart(m)}/${mEnd(m)}`, 'Moved to savings')}</td><td class="d-none d-md-table-cell" colspan="4"></td>
      <td class="text-end">${num(t.saved)}</td><td></td><td></td></tr>`;
  return `<div class="card mb-2">
    <div class="card-header d-flex align-items-center">
      <h3 class="card-title mb-0">Spending Record &amp; Plan</h3>
      <div class="ms-auto d-flex gap-1">${m.key === R.months[0].key && R.plan
        ? `<button class="btn btn-sm btn-outline-primary" data-plan="${new Date(R.today + 'T12:00').getDate() > 20 ? R.plan.next : m.key}"><em class="bi bi-pencil-square"></em> Plan</button>` : ''}
        ${link(`/budgets/${mStart(m)}/${mEnd(m)}`, '<em class="bi bi-pie-chart"></em> Budgets', 'btn btn-sm btn-outline-secondary')}</div>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-sm table-hover rec mb-0">
        <thead><tr><th>${esc(m.label)}</th>${m.weeks.map(w => `<th class="text-end d-none d-md-table-cell">${esc(w.label)}</th>`).join('')}
          <th class="text-end">Actual</th><th class="text-end">Plan</th><th class="text-end">(+) or (−)</th></tr></thead>
        <tbody>${body}</tbody>
      </table>
    </div>
  </div>`;
}

/* Ch. 10: on the 1st, last month's record line by line, then this month's plan,
   which must fit inside what comes in. A box holds a line's own amount, the
   base that repeats each month; Carried is what a rollover line brings with it
   from the month before (still to come, for next month). Saved through
   record/plan; the Ideal column (ch. 20) is kept in record_state. */
function planCard() {
  const P = R.plan, target = planning;
  const ref = target === R.months[0].key ? R.months[1] : R.months[0];
  const set = P.limits[target] || {}, ideal = R.state.ideal || {};
  const refLine = name => ref.lines.find(l => l.name === name) || { actual: 0, diff: 0, plan: 0 };
  const start = l => l.auto !== null ? l.auto : set[l.name] !== undefined ? set[l.name] : refLine(l.name).plan;
  const isNow = target === R.months[0].key;
  // this month: what Firefly added on the 1st; next month: what is left in the line so far
  const carried = l => l.type !== 'rollover' || l.auto === null ? null
    : isNow ? (set[l.name] !== undefined ? Math.max(0, set[l.name] - l.auto) : 0)
    : Math.max(0, ((R.months[0].lines.find(x => x.name === l.name) || {}).diff) || 0);
  let carriedTotal = 0;
  const box = (attr, v) => `<input type="number" class="form-control form-control-sm" ${attr} min="0" step="1" inputmode="decimal" value="${v === '' ? '' : Math.round(v * 100) / 100}">`;
  let body = '', group = null;
  P.lines.forEach(l => {
    if (l.group !== group) { group = l.group; body += `<tr class="grp"><td colspan="6">${esc(group)}</td></tr>`; }
    const r = refLine(l.name), c = carried(l);
    carriedTotal += c || 0;
    body += `<tr><td>${esc(l.name)}${l.pleasure ? ' <em class="bi bi-sun text-warning-emphasis" title="Pleasure line"></em>' : ''}</td>
      <td class="text-end">${num(r.actual)}</td>
      <td class="text-end d-none d-md-table-cell ${diffCls(Object.assign({ group: l.group, name: l.name }, r))}">${sign(r.diff)}</td>
      <td>${box(`data-pl="${esc(l.id)}"`, start(l))}</td>
      <td class="text-end d-none d-md-table-cell${isNow ? '' : ' text-body-secondary'}">${c ? '+' + num(c) : ''}</td>
      <td class="d-none d-md-table-cell">${box(`data-ideal="${esc(l.name)}"`, ideal[l.name] ?? '')}</td></tr>`;
  });
  if (ref.outside.length) {
    body += `<tr class="grp"><td colspan="6">Outside the plan</td></tr>` + ref.outside.map(o =>
      `<tr><td>${esc(o.name)}</td><td class="text-end">${num(o.actual)}</td><td class="d-none d-md-table-cell"></td><td></td><td class="d-none d-md-table-cell"></td><td class="d-none d-md-table-cell"></td></tr>`).join('');
  }
  body += `<tr class="tot"><td>Total</td><td class="text-end">${num(ref.totals.spent + ref.totals.saved)}</td>
    <td class="text-end d-none d-md-table-cell"></td><td class="text-end" id="plTot"></td>
    <td class="text-end d-none d-md-table-cell${isNow ? '' : ' text-body-secondary'}">${carriedTotal ? '+' + num(carriedTotal) : ''}</td>
    <td class="text-end d-none d-md-table-cell" id="idTot"></td></tr>`;
  const inc = R.income.months || [], lastFull = inc[inc.length - 1] || { month: ref.key, earned: 0 };
  const incRows = [[`Earned, ${monthName(lastFull.month).split(' ')[0]}`, lastFull.earned],
    ['Earned, 6-month average', R.income.mean_earned_6], ['Lowest of the six', inc.length ? Math.min(...inc.map(x => x.earned)) : 0]];
  const others = [R.months[0].key, P.next].map(k => `<button class="btn btn-sm ${k === target ? 'btn-secondary' : 'btn-outline-secondary'}" data-plan="${k}">${esc(monthName(k).split(' ')[0])}</button>`).join('');
  return `<div class="card mb-2">
    <div class="card-header d-flex align-items-center gap-2">
      <h3 class="card-title mb-0">Spending Plan</h3>
      <div class="btn-group ms-auto">${others}</div>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-sm table-hover rec mb-0">
        <thead><tr><th>${esc(monthName(target))}</th><th class="text-end">${esc(ref.label.split(' ')[0])}</th>
          <th class="text-end d-none d-md-table-cell">(+) or (−)</th><th class="text-end">Plan</th>
          <th class="text-end d-none d-md-table-cell">Carried</th><th class="text-end d-none d-md-table-cell">Ideal</th></tr></thead>
        <tbody>${body}</tbody>
      </table>
    </div>
    <div class="card-body border-top">
      <table class="table table-sm rec-kv mb-0">${incRows.map(([k, v]) =>
        `<tr><td>${k}</td><td>${usd0(v)}</td><td data-inc="${v}"></td></tr>`).join('')}</table>
    </div>
    <div class="card-footer d-flex gap-2 justify-content-end">
      ${link('/budgets/create', '<em class="bi bi-plus-lg"></em> Line', 'btn btn-sm btn-outline-secondary me-auto')}
      <button class="btn btn-sm btn-outline-secondary" data-plan="">Cancel</button>
      <button class="btn btn-sm btn-primary" id="planSave">Save</button>
    </div>
  </div>`;
}

/* the running totals under the plan, and how they sit against what comes in */
function planTotals() {
  const sum = sel => [...document.querySelectorAll(sel)].reduce((a, i) => a + Math.max(0, Number(i.value) || 0), 0);
  const plan = sum('[data-pl]'), ideal = sum('[data-ideal]');
  const t = document.getElementById('plTot');
  if (!t) return;
  t.textContent = num(plan);
  document.getElementById('idTot').textContent = ideal ? num(ideal) : '';
  document.querySelectorAll('[data-inc]').forEach(td => {
    const left = Number(td.dataset.inc) - plan;
    td.className = left >= 0 ? 'text-success' : 'text-danger';
    td.textContent = sign(left);
  });
}

async function savePlan(btn) {
  const lines = {}, ideal = {};
  document.querySelectorAll('[data-pl]').forEach(i => { lines[i.dataset.pl] = Math.max(0, Number(i.value) || 0); });
  document.querySelectorAll('[data-ideal]').forEach(i => { if (i.value !== '') ideal[i.dataset.ideal] = Math.max(0, Number(i.value) || 0); });
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  try {
    const was = R.updated;
    const r = await post('plan', { month: planning, lines });
    if (JSON.stringify(ideal) !== JSON.stringify(R.state.ideal || {})) R.state = (await post('state', { ideal })).state;
    planning = null;
    paint();
    if (r.changed) waitForFresh(was);
  } catch (e) {
    btn.disabled = false;
    btn.className = 'btn btn-sm btn-danger';
    btn.textContent = 'Not saved';
  }
}

function margin() {
  const last = R.months.find(x => x.complete) || R.months[1];
  const inc = R.income || {}, cur = R.months[0], mg = R.margin || {};
  const low = Math.min(...(inc.months || []).map(x => x.earned));
  const fits = cur.totals.plan <= inc.mean_earned_6;
  const s = last.surplus;
  const pleasure = last.lines.filter(l => l.pleasure && l.plan > 0 && l.actual < l.plan * 0.5);
  const row = (k, v, cls = '') => `<tr><td>${k}</td><td class="${cls}">${v}</td></tr>`;
  return `<div class="card mb-2"><div class="card-header"><h3 class="card-title">Margin</h3></div>
    <div class="card-body">
      <div class="rec-h">${esc(last.label)}</div>
      <table class="table table-sm rec-kv mb-0">
        ${row('Came in', usd(last.income.money_in))}
        ${row('Spent', usd(last.totals.spent))}
        ${row('Moved to savings', usd(last.totals.saved))}
        ${row(`<b>${s >= 0 ? 'Left over' : 'Spent beyond what came in'}</b>`, `<b>${usd(Math.abs(s))}</b>`, s >= 0 ? 'text-success' : '')}
        ${s > 0 ? row('Half to the plan', usd(s / 2)) + row('Half to savings', usd(s / 2)) : ''}
        ${pleasure.length ? row('Under half spent', `<span class="small">${pleasure.map(l => esc(l.name)).join(', ')}</span>`) : ''}
      </table>
      <div class="rec-h">${esc(cur.label.split(' ')[0])} <span class="badge ${fits ? 'text-bg-success' : 'text-bg-warning'}">${fits ? 'fits' : 'above average'}</span></div>
      <table class="table table-sm rec-kv mb-0">
        ${row('Planned', usd0(cur.totals.plan))}
        ${row('Earned, 6-month average', usd0(inc.mean_earned_6))}
        ${row('Lowest of the six', usd0(low))}
      </table>
      <div class="rec-h">Contingency fund</div>
      <table class="table table-sm rec-kv mb-0">
        ${row(maybe(mg.account && acctHref(mg.account), 'Savings'), usd0(mg.savings))}
        ${row('Living costs a month', usd0(mg.living_plan))}
        ${row('Months covered', `${num(mg.months_covered, 1)} <span class="text-body-secondary">of 6–12</span>`)}
      </table>
      <div class="progress mt-1" style="height: .4rem" role="progressbar" aria-label="Months covered" aria-valuenow="${num(mg.months_covered, 1)}" aria-valuemin="0" aria-valuemax="6">
        <div class="progress-bar bg-success" style="width: ${Math.min(100, (mg.months_covered || 0) / 6 * 100)}%"></div></div>
    </div></div>`;
}

/* Earn What You Deserve: the income side. Underearning is "repeatedly to gain
   less income than you need" (ch. 1) and is self-diagnosed, so the page shows
   the months and leaves the reading to the person. Clients are channels, not
   the source (ch. 7); their shares are the Diversification picture (ch. 5),
   and the years are the earning history of Step One. */
function earnings() {
  const e = R.earnings;
  if (!e) return '';
  const full = e.months.filter(x => x.complete);
  const sum = k => full.reduce((a, x) => a + x[k], 0);
  const catMonth = (name, key) => {
    const id = R.ids.categories[name];
    if (!id) return null;
    const [y, mo] = key.split('-').map(Number);
    return `/categories/show/${id}/${key}-01/${key}-${pad2(new Date(y, mo, 0).getDate())}`;
  };
  const dcls = (d, done = true) => !done ? '' : d > 0.004 ? 'text-success' : d < -0.004 ? 'text-danger' : 'text-warning';
  const mrow = x => `<tr class="${x.complete ? '' : 'text-body-secondary'}">
      <td class="text-nowrap"><a href="record?month=${esc(x.month)}" data-goto="${esc(x.month)}">${esc(monShort(x.month))}</a></td>
      <td class="text-end">${maybe(catMonth('Client income', x.month), num(x.earned))}</td>
      <td class="text-end">${num(x.spent)}</td>
      <td class="text-end ${dcls(x.earned - x.spent, x.complete)}">${sign(x.earned - x.spent)}</td>
      <td class="text-end d-none d-sm-table-cell">${x.other ? num(x.other) : ''}</td></tr>`;
  const via = c => c.via.filter(v => v !== 'Direct').map(v => `<span class="badge text-bg-light border fw-normal">${esc(v)}</span>`).join(' ');
  const lastPaid = d => fmtDay(d, d.slice(0, 4) === R.today.slice(0, 4) ? { month: 'short', day: 'numeric' } : { month: 'short', day: 'numeric', year: 'numeric' });
  const crow = c => `<tr><td>${esc(c.name)} ${via(c)}</td>
      <td class="text-end">${c.six ? num(c.six, 0) : '—'}</td>
      <td class="text-end">${c.six ? Math.round(c.share * 100) + '%' : ''}</td>
      <td class="text-end text-nowrap">${esc(lastPaid(c.last))}</td></tr>`;
  const yrow = y => `<tr class="${y.year === R.today.slice(0, 4) ? 'text-body-secondary' : ''}"><td>${esc(y.year)}</td>
      <td class="text-end">${num(y.earned, 0)}</td>
      <td class="text-end d-none d-sm-table-cell">${num(y.other, 0)}</td>
      <td class="text-end d-none d-sm-table-cell">${y.clients}</td>
      <td>${esc(y.top || '')}${y.top ? ` <span class="rec-sub">${Math.round(y.top_share * 100)}%</span>` : ''}</td></tr>`;
  const six = `${monShort(e.since, { month: 'short' })}–${monShort(R.months[1].key, { month: 'short' })}`;
  return `<div class="card mb-2"><div class="card-header"><h3 class="card-title">Earnings</h3></div>
    <div class="card-body rec-earn"><div class="rec-earn-grid">
      <div class="table-responsive">
        <table class="table table-sm rec mb-0">
          <thead><tr><th>Month</th><th class="text-end">Earned</th><th class="text-end">Spent</th><th class="text-end">(+) or (−)</th><th class="text-end d-none d-sm-table-cell">Other in</th></tr></thead>
          <tbody>${[...e.months].reverse().map(mrow).join('')}
            <tr class="tot"><td class="text-nowrap">${full.length} months</td><td class="text-end">${num(sum('earned'))}</td><td class="text-end">${num(sum('spent'))}</td>
              <td class="text-end ${dcls(sum('earned') - sum('spent'))}">${sign(sum('earned') - sum('spent'))}</td>
              <td class="text-end d-none d-sm-table-cell">${num(sum('other'))}</td></tr></tbody>
        </table>
      </div>
      <div class="rec-earn-side">
        <div class="table-responsive"><div class="rec-h">Clients</div>
        <table class="table table-sm rec mb-0">
          <thead><tr><th></th><th class="text-end text-nowrap">${esc(six)}</th><th class="text-end">Share</th><th class="text-end text-nowrap">Last paid</th></tr></thead>
          <tbody>${e.clients.map(crow).join('')}</tbody>
        </table></div>
        <div class="table-responsive"><div class="rec-h">By year</div>
        <table class="table table-sm rec mb-0">
          <thead><tr><th></th><th class="text-end">Earned</th><th class="text-end d-none d-sm-table-cell text-nowrap">Other in</th><th class="text-end d-none d-sm-table-cell">Clients</th><th>Largest</th></tr></thead>
          <tbody>${[...e.years].reverse().map(yrow).join('')}</tbody>
        </table></div>
      </div>
    </div></div></div>`;
}

/* Ch. 3: do not take work that pays you less than you need, and never answer an
   offer on the spot. It is measured net, not gross (ch. 2): less what the
   channel takes and the tax set aside, against this month's plan without its
   Taxes line, per hour of the hours a month to be worked. */
function offer() {
  const e = R.earnings;
  if (!e) return '';
  const o = R.state.offer || {};
  const tax = o.tax ?? (e.tax_share !== null ? Math.round(e.tax_share * 100) : '');
  const vias = Object.entries(e.fees).sort((a, b) => b[1] - a[1]);
  const via = offerIn.via || vias[0][0];
  return `<div class="card mb-2"><div class="card-header"><h3 class="card-title">Offer</h3></div>
    <div class="card-body">
      <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label small mb-0" for="ofAmt">Amount</label>
          <div class="input-group input-group-sm"><span class="input-group-text">$</span>
            <input type="number" class="form-control" id="ofAmt" data-of="amt" min="0" step="1" inputmode="decimal" value="${esc(offerIn.amt)}"></div></div>
        <div class="col-6"><label class="form-label small mb-0" for="ofHrs">Hours</label>
          <input type="number" class="form-control form-control-sm" id="ofHrs" data-of="hrs" min="0" step="0.5" inputmode="decimal" value="${esc(offerIn.hrs)}"></div>
        <div class="col-12"><label class="form-label small mb-0" for="ofVia">Through</label>
          <select class="form-select form-select-sm" id="ofVia" data-of="via">${vias.map(([k, f]) =>
            `<option value="${esc(k)}"${k === via ? ' selected' : ''}>${esc(k)} · ${num(f * 100, 1)}%</option>`).join('')}</select></div>
      </div>
      <table class="table table-sm rec-kv mb-2">
        <tr><td>Fee</td><td id="ofFee"></td></tr>
        <tr><td>Tax</td><td id="ofTaxAmt"></td></tr>
        <tr class="fw-semibold"><td>You keep</td><td id="ofKeep"></td></tr>
        <tr><td>Per hour</td><td id="ofPer"></td></tr>
        <tr><td>Need per hour</td><td id="ofNeed"></td></tr>
      </table>
      <table class="table table-sm rec-kv mb-0">
        <tr><td>Need a month</td><td>${usd0(e.need)}</td></tr>
        <tr><td><label for="ofMonth">Hours a month</label></td>
          <td><input type="number" class="form-control form-control-sm ms-auto text-end" style="max-width: 6rem" id="ofMonth" min="0" max="744" step="1" inputmode="numeric" value="${esc(o.hours ?? '')}"></td></tr>
        <tr><td><label for="ofTax">Tax set aside</label></td>
          <td><div class="input-group input-group-sm ms-auto" style="max-width: 6.5rem">
            <input type="number" class="form-control text-end" id="ofTax" min="0" max="90" step="1" inputmode="numeric" value="${esc(tax)}"><span class="input-group-text">%</span></div></td></tr>
      </table>
    </div></div>`;
}

function offerCalc() {
  const el = id => document.getElementById(id);
  if (!el('ofKeep')) return;
  const e = R.earnings, amt = Number(offerIn.amt) || 0, hrs = Number(offerIn.hrs) || 0;
  const fee = amt * (e.fees[el('ofVia').value] || 0);
  const tax = (amt - fee) * (Number(el('ofTax').value) || 0) / 100;   // fees come off before tax
  const keep = amt - fee - tax, month = Number(el('ofMonth').value) || 0;
  const need = month ? e.need / month : null, per = amt && hrs ? keep / hrs : null;
  el('ofFee').textContent = amt ? '−' + usd(fee) : '';
  el('ofTaxAmt').textContent = amt ? '−' + usd(tax) : '';
  el('ofKeep').textContent = amt ? usd(keep) : '';
  el('ofPer').textContent = per !== null ? usd(per) : '';
  el('ofPer').className = per !== null && need !== null ? (per + 0.004 >= need ? 'text-success' : 'text-danger') : '';
  el('ofNeed').textContent = need !== null ? usd(need) : '';
}

const PER = { daily: 'a day', weekly: 'a week', monthly: 'a month', quarterly: 'a quarter', yearly: 'a year' };

function debts() {
  const d = R.debts, key = R.months[0].key;
  const paidNow = name => ((d.reduction.find(r => r.creditor === name) || {}).months || {})[key] || 0;
  const rows = d.active.map(x => `<tr><td>${maybe(acctHref(x.name), esc(x.name))}${x.estimate ? ' <span class="badge text-bg-light border">estimate</span>' : ''}
      <div class="rec-sub">${Math.round(x.share * 100)}%${x.interest ? ` · ${num(x.interest, x.interest % 1 ? 2 : 0)}% ${PER[x.interest_period] || ''}` : ''}</div></td>
    <td class="text-end">${num(x.balance)}</td>
    <td class="text-end" data-share="${x.share}">${pay ? usd(Math.max(1, pay * x.share)) : '—'}</td>
    <td class="text-end">${paidNow(x.name) ? usd(paidNow(x.name)) : ''}</td></tr>`).join('');
  const unl = (d.unlisted || {}).items || [];
  return `<div class="card mb-2"><div class="card-header"><h3 class="card-title">Debts</h3></div>
    <div class="card-body">
      <div class="input-group input-group-sm mb-2"><span class="input-group-text">Monthly amount</span><span class="input-group-text">$</span>
        <input type="number" class="form-control" id="pay" min="0" step="1" inputmode="numeric" value="${pay || ''}"></div>
      <table class="table table-sm mb-0">
        <thead><tr><th>Creditor</th><th class="text-end">Owed</th><th class="text-end">Each month</th><th class="text-end">Paid ${esc(fmtDay(R.today, { month: 'short' }))}</th></tr></thead>
        <tbody>${rows}<tr class="fw-semibold"><td>Total</td><td class="text-end">${num(d.total)}</td><td class="text-end" id="paytot">${pay ? usd(pay) : ''}</td>
          <td class="text-end">${(() => { const t = d.active.reduce((a, x) => a + paidNow(x.name), 0); return t ? usd(t) : ''; })()}</td></tr></tbody>
      </table>
      ${unl.length ? `<div class="rec-h">Not in Firefly · Debt Log ${esc(d.unlisted.updated)}</div>
        <table class="table table-sm rec-kv mb-0 text-muted">${unl.map(u => `<tr><td>${esc(u.name)}</td><td>₪${num(u.ils, 0)}</td></tr>`).join('')}</table>` : ''}
      ${(d.owed_to_you || []).length ? `<div class="rec-h">Owed to you</div>
        <table class="table table-sm rec-kv mb-0">${d.owed_to_you.map(o => `<tr><td>${maybe(acctHref(o.name), esc(o.name))}</td><td>${usd0(o.amount)}</td></tr>`).join('')}</table>` : ''}
    </div></div>`;
}

/* Ch. 17: the Liquidation Record (original amount, the day it was paid off) and
   the Reduction Record, a page per creditor: each payment and the new balance */
function records() {
  const d = R.debts;
  const liq = d.liquidated.map(x => `<tr><td>${maybe(acctHref(x.name), `<b>${esc(x.name)}</b>`)}<div class="rec-sub">${esc(x.date)}${x.paid && x.amount && Math.abs(x.paid - x.amount) > 0.005 ? ` · paid ${usd(x.paid)}` : ''}</div></td>
    <td class="text-end text-success">${x.amount ? usd(x.amount) : '✓'}</td></tr>`).join('');
  const red = d.reduction.map(x => {
    const pays = (x.payments || []).map(p => `<tr><td>${esc(p.date)}</td><td class="text-end">${usd(p.amount)}</td><td class="text-end">${p.balance !== null ? usd(p.balance) : ''}</td></tr>`);
    if (x.since) pays.splice((x.payments || []).filter(p => p.date <= x.since).length, 0,
      `<tr class="text-body-secondary"><td>${esc(x.since)}</td><td></td><td class="text-end">${usd(x.register)}</td></tr>`);
    return `<details class="rec-red"><summary><span>${esc(x.creditor)}
        <span class="rec-sub d-block">${x.count} payment${x.count === 1 ? '' : 's'} · ${esc(x.first)} → ${esc(x.last)}</span></span>
        <span class="amt">${usd0(x.total)}</span></summary>
      <table class="table table-sm mb-1"><thead><tr><th>Date</th><th class="text-end">Paid</th><th class="text-end">Balance</th></tr></thead>
        <tbody>${pays.join('')}</tbody></table>
      ${acctHref(x.creditor) ? `<div class="small">${link(acctHref(x.creditor), esc(x.creditor) + ' <em class="bi bi-arrow-right-circle"></em>')}</div>` : ''}
    </details>`;
  }).join('');
  return `<div class="card mb-2"><div class="card-header"><h3 class="card-title">Records</h3></div>
    <div class="card-body">
      <div class="rec-h">Liquidation Record</div>
      <table class="table table-sm rec-kv mb-0">${liq}</table>
      <div class="rec-h">Reduction Record</div>
      ${red}
    </div></div>`;
}

function updated() {
  if (!R.updated) return '';
  const age = (Date.now() - new Date(R.updated)) / 60000;
  const t = new Date(R.updated).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
  return `<span class="small me-1 ${age > 120 ? 'text-warning-emphasis' : 'text-body-secondary'}">Updated ${t}</span>`;
}

/* every month from the oldest transaction to this one */
function monthKeys() {
  const out = [], end = R.months[0].key;
  let [y, mo] = (R.first_month || R.months[R.months.length - 1].key).split('-').map(Number);
  for (let k = `${y}-${pad2(mo)}`; k <= end; k = `${y}-${pad2(mo)}`) {
    out.push(k);
    if (++mo > 12) { mo = 1; y++; }
  }
  return out;
}
const monthName = k => new Date(k + '-01T12:00').toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
const monShort = (k, o = { month: 'short', year: 'numeric' }) => new Date(k + '-01T12:00').toLocaleDateString('en-US', o);

function picker() {
  const keys = monthKeys(), i = keys.indexOf(view);
  return `<div class="input-group input-group-sm w-auto flex-nowrap">
    <button class="btn btn-outline-secondary" data-step="-1" aria-label="Previous month"${i <= 0 ? ' disabled' : ''}><em class="bi bi-chevron-left"></em></button>
    <select class="form-select" id="month" aria-label="Month">${[...keys].reverse().map(k =>
      `<option value="${k}"${k === view ? ' selected' : ''}>${esc(monthName(k))}</option>`).join('')}</select>
    <button class="btn btn-outline-secondary" data-step="1" aria-label="Next month"${i >= keys.length - 1 ? ' disabled' : ''}><em class="bi bi-chevron-right"></em></button>
  </div>`;
}

function paint() {
  const boxKey = i => i.dataset.pl ? 'p' + i.dataset.pl : 'i' + i.dataset.ideal;
  const typed = planning && planning === drawn
    ? Object.fromEntries([...document.querySelectorAll('[data-pl], [data-ideal]')].map(i => [boxKey(i), i.value])) : {};
  const m = cache[view];
  const sub = document.getElementById('test-box');
  if (sub) sub.textContent = monthName(view);
  if (sheet) {
    const q = document.activeElement && document.activeElement.id === 'shq' ? document.activeElement.selectionStart : null;
    // a redraw of the same view keeps where the sheet was scrolled to, and an open picker
    const same = sh.drawn === shKey();
    const wrap = document.querySelector('.sh-wrap'), at = wrap && same ? [wrap.scrollTop, wrap.scrollLeft] : null;
    sh.still = true;
    document.getElementById('record-page').innerHTML = bar() + (m ? sheetView(m)
      : '<div class="card mb-2"><div class="card-body text-center text-body-secondary py-5"><span class="spinner-border spinner-border-sm"></span></div></div>');
    sh.drawn = shKey();
    const w2 = document.querySelector('.sh-wrap');
    if (at && w2) [w2.scrollTop, w2.scrollLeft] = at;
    if (sh.pk) {
      const el = document.querySelector(`[data-sh-id="${sh.pk.jid}"] [data-sh-edit="${sh.pk.field}"]`);
      if (el) { sh.pk.el = el; el.classList.add('sh-active'); }
    }
    setTimeout(() => { sh.still = false; }, 200);
    shAfterPaint();
    const qi = document.getElementById('shq');
    if (q !== null && qi) { qi.focus(); qi.setSelectionRange(q, q); }
    return;
  }
  const month = m
    ? `<div class="row"><div class="col-lg-8">${daily(m)}</div><div class="col-lg-4">${dayCard(m)}</div></div>`
      + `<div class="row"><div class="col-12">${planning ? planCard() : table(m)}</div></div>`
    : '<div class="card mb-2"><div class="card-body text-center text-body-secondary py-5"><span class="spinner-border spinner-border-sm"></span></div></div>';
  document.getElementById('record-page').innerHTML = boxes(m) + bar() + month
    + `<div class="row"><div class="col-xl-8">${earnings()}</div><div class="col-xl-4 col-md-6">${offer()}</div></div>`
    + `<div class="row"><div class="col-xl-4 col-md-6">${margin()}</div><div class="col-xl-4 col-md-6">${debts()}</div><div class="col-xl-4 col-md-6">${records()}</div></div>`;
  document.querySelectorAll('[data-pl], [data-ideal]').forEach(i => { if (boxKey(i) in typed) i.value = typed[boxKey(i)]; });
  drawn = planning;
  planTotals();
  offerCalc();
}

/* ---------- Sheet: every charge of a week or the month as rows, each one's
   category, line and note changed in place (record/tx). Tabs at the bottom
   like the workbook's: the four weeks, the month, and Lines. ---------- */
const sh = {
  tab: null, tabOf: null,          // a week's index, 'month' or 'lines'; for which month
  filt: 'all', q: '', line: null,  // what is shown
  sortBy: 'date', sortDir: 1,
  edits: {},                       // journal id → { category?, line?, note?, st }
  touched: new Set(),              // cells changed on this page: 'jid:field'
  failed: new Set(),
  flash: null, pk: null,
  drawn: null, still: false,       // the view last drawn; true while a redraw settles
};
const shKey = () => [view, sh.tab, sh.filt, sh.line, sh.sortBy, sh.sortDir, shNarrow()].join('|');
const shNarrow = () => matchMedia('(max-width: 575.98px)').matches;
const shDay = (d, o) => fmtDay(d, o);
const shGroups = () => [...new Set(R.plan.lines.map(l => l.group))];
const shSw = g => 'sh-g' + Math.max(0, shGroups().indexOf(g));
const shPlanLine = name => R.plan.lines.find(l => l.name === name);

function shRow(m, it) {
  const e = sh.edits[it.jid] || {};
  return {
    ...it,
    category: 'category' in e ? e.category : (it.nocat ? '' : it.category),
    line: 'line' in e ? e.line : it.line,
    note: it.note || '',
    comment: 'comment' in e ? e.comment : (it.comment || ''),
    week: m.days.find(d => d.date === it.date).week,
  };
}

function shItems(m) {
  return m.days.flatMap(d => d.items.map(it => ({ ...it, date: d.date })));
}

/* drop what the page itself remembered once the numbers carry the change */
function shPrune(m) {
  shItems(m).forEach(it => {
    const e = sh.edits[it.jid];
    if (!e || e.st !== 'saved') return;
    const same = (!('category' in e) || e.category === (it.nocat ? '' : it.category))
      && (!('line' in e) || e.line === it.line) && (!('comment' in e) || e.comment === (it.comment || ''));
    if (same) delete sh.edits[it.jid];
  });
}

function shQuiet(m) { return new Set(m.outside.filter(o => o.quiet).map(o => o.name)); }

function shKind(m, r, quiet) {
  if (!r.category) return 'nocat';
  if (!m.planned) return 'plan';
  if (R.ids.budgets[r.category] && r.line !== r.category) return 'mismatch';
  if (!r.line) return quiet.has(r.category) ? 'quiet' : 'outside';
  return 'plan';
}

/* each line's weeks for the month, with changes not yet in the numbers moved across */
function shLines(m) {
  const L = {};
  m.lines.forEach(l => { L[l.name] = { name: l.name, plan: l.plan, group: (shPlanLine(l.name) || {}).group || l.group, weeks: [...l.weeks], inPlan: true }; });
  m.outside.forEach(o => { L[o.name] = { name: o.name, weeks: [...o.weeks], quiet: o.quiet }; });
  const put = (k, w, a) => {
    if (!L[k]) L[k] = { name: k, weeks: m.weeks.map(() => 0) };
    L[k].weeks[w] = Math.round((L[k].weeks[w] + a) * 100) / 100;
  };
  const bucket = (line, cat) => line || cat || 'Uncategorised';
  shItems(m).forEach(it => {
    if (!sh.edits[it.jid]) return;
    const r = shRow(m, it);
    const a = bucket(it.line, it.category), b = bucket(r.line, r.category);
    if (a !== b) { put(a, r.week, -it.amount); put(b, r.week, it.amount); }
  });
  Object.values(L).forEach(l => {
    l.actual = l.weeks.reduce((s, x) => s + x, 0);
    if (l.inPlan) l.diff = l.plan - l.actual;
  });
  return L;
}

const shLs = (m, l) => (m.planned && l && l.inPlan ? diffCls(l) : '');

function shVisible(m, quiet) {
  let rows = shItems(m).map(it => shRow(m, it));
  if (sh.tab !== 'month') rows = rows.filter(r => r.week === sh.tab);
  const all = rows;
  const ks = rows.map(r => shKind(m, r, quiet));
  const n = {
    all: rows.length,
    sort: ks.filter(k => k === 'nocat' || k === 'mismatch').length,
    outside: ks.filter(k => k === 'outside').length,
    pending: rows.filter(r => r.pending).length,
  };
  rows = rows.filter((r, i) => sh.filt === 'all' || (sh.filt === 'sort' && (ks[i] === 'nocat' || ks[i] === 'mismatch'))
    || (sh.filt === 'outside' && ks[i] === 'outside') || (sh.filt === 'pending' && r.pending));
  if (sh.line) rows = rows.filter(r => r.line === sh.line || (!r.line && (r.category || 'Uncategorised') === sh.line));
  if (sh.q) {
    const s = sh.q.toLowerCase();
    rows = rows.filter(r => [r.what, r.category, r.line, r.account, r.note, r.comment, num(r.amount)].some(v => (v || '').toLowerCase().includes(s)));
  }
  const key = { date: r => r.date, what: r => r.what.toLowerCase(), amount: r => r.amount, category: r => r.category.toLowerCase(),
    line: r => (r.line || '~').toLowerCase(), account: r => r.account }[sh.sortBy];
  rows.sort((a, b) => (key(a) < key(b) ? -1 : key(a) > key(b) ? 1 : 0) * sh.sortDir || a.date.localeCompare(b.date) || b.amount - a.amount);
  return { rows, n, all };
}

function sheetView(m) {
  if (sh.tabOf !== m.key) {
    const t = m.days.find(d => d.date === R.today);
    sh.tab = t ? t.week : 'month';
    sh.tabOf = m.key;
    sh.line = null;
  }
  shPrune(m);
  const L = shLines(m), quiet = shQuiet(m);
  return `<div class="card mb-2 sh-card">${sh.tab === 'lines' ? shLinesTab(m, L) : shCharges(m, L, quiet)}${shFoot(m, quiet)}</div>`;
}

function shCharges(m, L, quiet) {
  const { rows, n } = shVisible(m, quiet);
  const chip = (k, label) => {
    const on = sh.filt === k, warn = k === 'sort' && n.sort;
    return `<button class="btn btn-sm ${on ? 'btn-secondary' : warn ? 'btn-outline-danger' : 'btn-outline-secondary'}" data-sh-filt="${k}">${label}<span class="badge ms-1 ${on ? 'text-bg-light' : warn ? 'text-bg-danger' : 'text-bg-secondary'}">${n[k]}</span></button>`;
  };
  const head = `<div class="card-header d-flex flex-wrap align-items-center gap-1">
    ${chip('all', 'All')}${m.planned ? chip('sort', 'To sort') + chip('outside', 'Outside the plan') : ''}${chip('pending', 'Pending')}
    ${sh.line ? `<button class="btn btn-sm btn-primary" data-sh-unline>${esc(sh.line)} <em class="bi bi-x"></em></button>` : ''}
    <input class="form-control form-control-sm sh-q ms-auto" type="search" id="shq" placeholder="Search" aria-label="Search" value="${esc(sh.q)}">
  </div>`;
  return head + (shNarrow() ? shPhone(m, rows, quiet, L) : shTable(m, rows, quiet, L));
}

function shCellCls(r, field, extra = '') {
  const k = r.jid + ':' + field;
  return `${extra}${sh.touched.has(k) ? ' sh-edited' : ''}${sh.failed.has(k) ? ' sh-failed' : ''}${(sh.edits[r.jid] || {}).st === 'saving' && sh.touched.has(k) ? ' sh-saving' : ''}`;
}

function shSwatch(m, r, k) {
  if (k === 'nocat') return 'sh-none';
  if (k === 'quiet') return 'sh-quiet';
  const l = shPlanLine(r.line) || shPlanLine(r.category);
  return l && k !== 'outside' ? shSw(l.group) : 'sh-out';
}

function shLineText(r, k) {
  if (r.line) return esc(r.line);
  return k === 'outside' ? 'Outside the plan' : '—';
}

function shTable(m, rows, quiet, L) {
  const grouped = sh.sortBy === 'date';
  let html = '', lastDate = null, lastWeek = null, i = 0;
  const weekRow = w => `<tr class="sh-weekrow"><td class="sh-rn"></td><td colspan="2">${esc(m.weeks[w].label)}</td><td class="sh-num">${num(m.totals.weeks[w] || 0)}</td><td colspan="5"></td></tr>`;
  rows.forEach(r => {
    if (grouped && r.date !== lastDate) {
      if (sh.tab === 'month' && lastWeek !== null && r.week !== lastWeek) html += weekRow(lastWeek);
      const d = m.days.find(x => x.date === r.date);
      html += `<tr class="sh-dayrow sh-s-${d.status}"><td class="sh-rn"></td><td colspan="2">${esc(shDay(r.date, { weekday: 'long', month: 'short', day: 'numeric' }))}${
        d.crossed.length ? `<span class="sh-crossed text-danger">${d.crossed.map(esc).join(', ')}</span>` : ''}</td>
        <td class="sh-num"><span class="sh-pill sh-s-${d.status}">${num(d.total)}</span></td><td colspan="5"></td></tr>`;
      lastDate = r.date; lastWeek = r.week;
    }
    const k = shKind(m, r, quiet);
    const [payee, ...rest] = r.what.split(' — ');
    const can = !!r.jid;
    html += `<tr data-sh-id="${esc(r.jid || '')}" class="${r.pending ? 'sh-pending' : ''}">
      <td class="sh-rn">${++i}</td>
      <td class="sh-dt${grouped ? ' sh-rep' : ''}">${esc(shDay(r.date, { weekday: 'short', day: 'numeric' }))}</td>
      <td class="sh-what" title="${esc(r.what)}">${link('/transactions/show/' + r.id, esc(payee))}${rest.length ? ` <small>${esc(rest.join(' — '))}</small>` : ''}</td>
      <td class="sh-num sh-amt">${num(r.amount)}${r.native ? ` <span class="sh-native">${esc(r.native)}</span>` : ''}</td>
      <td class="${shCellCls(r, 'category', can ? 'sh-cell' : '')}${k === 'nocat' ? ' sh-bad' : ''}"${can ? ' data-sh-edit="category"' : ''}><div class="sh-cv"><i class="sh-sw ${shSwatch(m, r, k)}"></i>${r.category ? esc(r.category) : '—'}${can ? '<em class="bi bi-caret-down-fill sh-caret"></em>' : ''}</div></td>
      <td class="${shCellCls(r, 'line', can ? 'sh-cell' : '')} ${k === 'mismatch' ? 'sh-bad' : k === 'outside' ? 'sh-out' : shLs(m, L[r.line])}"${can ? ' data-sh-edit="line"' : ''}><div class="sh-cv">${shLineText(r, k)}${can ? '<em class="bi bi-caret-down-fill sh-caret"></em>' : ''}</div></td>
      <td>${esc(r.account)}${r.pending ? ' <span class="badge bg-body-secondary text-body-secondary border fw-normal">Pending</span>' : ''}</td>
      <td class="${shCellCls(r, 'comment', can ? 'sh-cell sh-comment' : 'sh-comment')}"${can ? ' data-sh-edit="comment"' : ''}>${esc(r.comment)}</td>
      <td class="sh-note">${esc(r.note)}</td>
    </tr>`;
  });
  if (grouped && sh.tab === 'month' && lastWeek !== null) html += weekRow(lastWeek);
  if (!rows.length) html = '<tr class="sh-empty"><td colspan="9">No charges</td></tr>';
  const th = (k, label, cls = '') => `<th data-sh-sort="${k}" class="${cls}">${label}${sh.sortBy === k ? `<em class="bi bi-caret-${sh.sortDir > 0 ? 'up' : 'down'}-fill"></em>` : ''}</th>`;
  return `<div class="sh-wrap"><table class="sh"><thead><tr><th class="sh-rn"></th>${th('date', 'Date')}${th('what', 'Description')}${
    th('amount', 'Amount', 'sh-num')}${th('category', 'Category')}${th('line', 'Line')}${th('account', 'Account')}<th>Comment</th><th>Note</th></tr></thead><tbody>${html}</tbody></table></div>`;
}

function shPhone(m, rows, quiet, L) {
  const grouped = sh.sortBy === 'date';
  let html = '', lastDate = null, lastWeek = null;
  const weekRow = w => `<div class="sh-m-week"><span>${esc(m.weeks[w].label)}</span><span>${num(m.totals.weeks[w] || 0)}</span></div>`;
  rows.forEach(r => {
    if (grouped && r.date !== lastDate) {
      if (sh.tab === 'month' && lastWeek !== null && r.week !== lastWeek) html += weekRow(lastWeek);
      const d = m.days.find(x => x.date === r.date);
      html += `<div class="sh-m-day"><span>${esc(shDay(r.date, { weekday: 'short', month: 'short', day: 'numeric' }))}${
        d.crossed.length ? ` <span class="text-danger fw-normal small">${d.crossed.map(esc).join(', ')}</span>` : ''}</span><span class="sh-pill sh-s-${d.status}">${num(d.total)}</span></div>`;
      lastDate = r.date; lastWeek = r.week;
    }
    const k = shKind(m, r, quiet);
    const [payee] = r.what.split(' — ');
    const can = !!r.jid;
    const ed = f => (can ? ` data-sh-edit="${f}"` : ' disabled');
    html += `<div class="sh-m-row${r.pending ? ' sh-pending' : ''}" data-sh-id="${esc(r.jid || '')}">
      <div class="sh-m-top">${link('/transactions/show/' + r.id, esc(payee), 'sh-m-what')}<span class="sh-m-acct">${esc(r.account)}${r.pending ? ' · Pending' : ''}</span>${
        r.native ? `<span class="sh-native">${esc(r.native)}</span>` : ''}<span class="sh-m-amt">${num(r.amount)}</span></div>
      <div class="sh-m-bot">
        <button class="sh-chip${shCellCls(r, 'category')}${k === 'nocat' ? ' sh-bad' : ''}"${ed('category')}><i class="sh-sw ${shSwatch(m, r, k)}"></i>${r.category ? esc(r.category) : 'Category'}</button>
        <button class="sh-chip${shCellCls(r, 'line')} ${k === 'mismatch' ? 'sh-bad' : k === 'outside' ? 'sh-out' : shLs(m, L[r.line])}"${ed('line')}>${shLineText(r, k)}</button>
        <button class="sh-chip${shCellCls(r, 'comment')}"${ed('comment')} aria-label="Comment"><em class="bi bi-pencil"></em></button>
      </div>${r.comment ? `<div class="sh-m-comment">${esc(r.comment)}</div>` : ''}${r.note ? `<div class="sh-m-note">${esc(r.note)}</div>` : ''}
    </div>`;
  });
  if (grouped && sh.tab === 'month' && lastWeek !== null) html += weekRow(lastWeek);
  if (!rows.length) html = '<div class="text-center text-body-secondary py-5">No charges</div>';
  return `<div class="sh-wrap">${html}</div>`;
}

function shLinesTab(m, L) {
  const wk = (l, i) => `<td class="sh-num sh-wk" data-sh-line="${esc(l.name)}" data-sh-week="${i}">${l.weeks[i] ? num(l.weeks[i]) : ''}</td>`;
  const span = m.weeks.length + (m.planned ? 4 : 2);
  const rowOf = l => `<tr>
    <td class="sh-name${l.quiet ? ' text-body-secondary' : ''}" data-sh-line="${esc(l.name)}"><span class="d-inline-flex align-items-center"><i class="sh-sw ${
      l.inPlan ? shSw(l.group) : l.quiet ? 'sh-quiet' : l.name === 'Uncategorised' ? 'sh-none' : 'sh-out'}"></i>${esc(l.name)}</span></td>
    ${m.weeks.map((_, i) => wk(l, i)).join('')}
    <td class="sh-num">${num(l.actual)}</td>
    ${m.planned ? `<td class="sh-num">${l.inPlan ? num(l.plan) : ''}</td><td class="sh-num ${shLs(m, l)}">${l.inPlan ? sign(l.diff) : ''}</td>` : ''}</tr>`;
  const plan = Object.values(L).filter(l => l.inPlan && (m.planned || l.actual > 0.004));
  let body = '';
  shGroups().concat(plan.some(l => !shGroups().includes(l.group)) ? ['Other lines'] : []).forEach(g => {
    const ls = plan.filter(l => (shGroups().includes(l.group) ? l.group : 'Other lines') === g);
    if (ls.length) body += `<tr class="sh-grp"><td colspan="${span}">${esc(g)}</td></tr>` + ls.map(rowOf).join('');
  });
  const tw = m.weeks.map((_, i) => plan.reduce((s, l) => s + l.weeks[i], 0));
  const ta = plan.reduce((s, l) => s + l.actual, 0), tp = plan.reduce((s, l) => s + l.plan, 0);
  const tot = (label, w, a, p) => `<tr class="sh-tot"><td>${label}</td>${w.map(x => `<td class="sh-num">${num(x)}</td>`).join('')}<td class="sh-num">${num(a)}</td>${
    m.planned ? (p === null ? '<td></td><td></td>' : `<td class="sh-num">${num(p)}</td><td class="sh-num ${diffCls({ name: '', group: '', diff: p - a })}">${sign(p - a)}</td>`) : ''}</tr>`;
  if (plan.length) body += tot(m.planned ? 'Plan' : 'Lines', tw, ta, m.planned ? tp : null);
  const out = Object.values(L).filter(l => !l.inPlan && l.actual > 0.004);
  if (out.length) {
    body += `<tr class="sh-grp"><td colspan="${span}">${m.planned ? 'Outside the plan' : 'No line'}</td></tr>` + out.map(rowOf).join('');
    const ow = m.weeks.map((_, i) => tw[i] + out.reduce((s, l) => s + l.weeks[i], 0));
    body += tot('All spending', ow, ta + out.reduce((s, l) => s + l.actual, 0), null);
  }
  return `<div class="sh-wrap sh-lines-wrap"><table class="sh sh-lines"><thead><tr><th>Line</th>${
    m.weeks.map(w => `<th class="sh-num">${esc(w.label)}</th>`).join('')}<th class="sh-num">Actual</th>${
    m.planned ? '<th class="sh-num">Plan</th><th class="sh-num">(+) or (−)</th>' : ''}</tr></thead><tbody>${body}</tbody></table></div>`;
}

function shFoot(m, quiet) {
  const cur = m.days.find(d => d.date === R.today);
  const tabs = m.weeks.map((w, i) => `<button class="${sh.tab === i ? 'sh-on' : ''}${cur && cur.week === i ? ' sh-today' : ''}" data-sh-tab="${i}">${esc(w.label)}</button>`).join('')
    + `<button class="${sh.tab === 'month' ? 'sh-on' : ''}" data-sh-tab="month">${esc(monShort(m.key, { month: 'long' }))}</button>`
    + `<button class="${sh.tab === 'lines' ? 'sh-on' : ''}" data-sh-tab="lines">Lines</button>`;
  let sum;
  if (sh.tab === 'lines') {
    sum = `${m.planned ? `Plan<b>${num(m.totals.plan)}</b> &nbsp; ` : ''}Spent<b>${num(m.totals.spent)}</b>`;
  } else {
    const { rows } = shVisible(m, quiet);
    sum = `Count<b>${rows.length}</b> &nbsp; Sum<b>${num(rows.reduce((s, r) => s + r.amount, 0))}</b>`;
  }
  return `<div class="sh-foot"><div class="sh-tabs">${tabs}</div><div class="sh-sum">${sum}</div></div>`;
}

/* the picker: a list for category and line, a box for the note */
function shOpen(el) {
  const jid = el.closest('[data-sh-id]').dataset.shId;
  const field = el.dataset.shEdit;
  const m = cache[view];
  const it = shItems(m).find(x => x.jid === jid);
  if (!it) return;
  shClose();
  el.classList.add('sh-active');
  const r = shRow(m, it);
  const back = document.createElement('div');
  back.className = 'sh-pk-back';
  const pk = document.createElement('div');
  pk.className = 'sh-pk';
  pk.innerHTML = field === 'comment'
    ? `<input class="form-control form-control-sm" type="text" maxlength="400" aria-label="Comment" placeholder="Comment" value="${esc(r.comment)}">
       <div class="sh-pk-btns"><button class="btn btn-sm btn-outline-secondary" data-sh-cancel>Cancel</button><button class="btn btn-sm btn-primary" data-sh-ok>Save</button></div>`
    : `<input class="form-control form-control-sm" type="search" placeholder="${field === 'category' ? 'Category' : 'Line'}" aria-label="${field === 'category' ? 'Category' : 'Line'}"><div class="sh-pk-list"></div>`;
  document.body.append(back, pk);
  sh.pk = { jid, field, el, pk, back, hl: 0 };
  if (field !== 'comment') shFill('');
  shPlace();
  if (field === 'comment') {
    const ti = pk.querySelector('input');
    ti.focus({ preventScroll: true });
    ti.setSelectionRange(ti.value.length, ti.value.length);
  } else if (!shNarrow()) pk.querySelector('input').focus({ preventScroll: true });
}

function shPlace() {
  const { el, pk } = sh.pk;
  if (shNarrow()) return;
  const b = el.getBoundingClientRect();
  const h = Math.min(pk.offsetHeight, 384);
  const below = innerHeight - b.bottom > h + 8;
  pk.style.left = Math.max(8, Math.min(b.left, innerWidth - pk.offsetWidth - 8)) + 'px';
  pk.style.top = (below ? b.bottom + 2 : Math.max(8, b.top - h - 2)) + 'px';
}

function shFill(text) {
  const { jid, field, pk } = sh.pk;
  const m = cache[view];
  const r = shRow(m, shItems(m).find(x => x.jid === jid));
  const L = shLines(m);
  const t = text.trim().toLowerCase();
  const match = s => !t || s.toLowerCase().includes(t);
  const cur = field === 'category' ? r.category : (r.line || '');
  const btn = (v, label, extra = '') => `<button data-v="${esc(v)}" class="${v === cur ? 'sh-cur' : ''}">${label}${extra}</button>`;
  const left = name => (m.planned && L[name] && L[name].inPlan ? `<span class="sh-r ${shLs(m, L[name])}">${sign(L[name].diff)}</span>` : '');
  let html = '';
  shGroups().forEach(g => {
    const ls = R.plan.lines.filter(l => l.group === g && match(l.name));
    if (ls.length) html += `<div class="sh-pk-h">${esc(g)}</div>` + ls.map(l => btn(l.name, `<i class="sh-sw ${shSw(g)}"></i>${esc(l.name)}`, left(l.name))).join('');
  });
  if (field === 'category') {
    const cats = (R.sheet || {}).categories || Object.keys(R.ids.categories);
    const others = cats.filter(c => !shPlanLine(c) && match(c));
    if (others.length) html += '<div class="sh-pk-h">Outside the plan</div>' + others.map(c => btn(c, `<i class="sh-sw sh-out"></i>${esc(c)}`)).join('');
    const known = cats.concat(R.plan.lines.map(l => l.name)).some(c => c.toLowerCase() === t);
    if (t && !known && text.trim().length <= 100) html += `<div class="sh-pk-h">New</div><button data-v="${esc(text.trim())}"><em class="bi bi-plus-lg me-2"></em>${esc(text.trim())}</button>`;
  } else if (match('Outside the plan')) {
    html += '<div class="sh-pk-h">—</div>' + btn('', '<i class="sh-sw sh-out"></i>Outside the plan');
  }
  pk.querySelector('.sh-pk-list').innerHTML = html;
  const bs = [...pk.querySelectorAll('.sh-pk-list button')];
  sh.pk.hl = t ? 0 : Math.max(0, bs.findIndex(b => b.classList.contains('sh-cur')));
  shHl();
}

function shHl() {
  const bs = [...sh.pk.pk.querySelectorAll('.sh-pk-list button')];
  bs.forEach((b, i) => b.classList.toggle('sh-hl', i === sh.pk.hl));
  const b = bs[sh.pk.hl], list = sh.pk.pk.querySelector('.sh-pk-list');
  if (b && list) {
    if (b.offsetTop < list.scrollTop) list.scrollTop = b.offsetTop - 24;
    else if (b.offsetTop + b.offsetHeight > list.scrollTop + list.clientHeight) list.scrollTop = b.offsetTop + b.offsetHeight - list.clientHeight;
  }
}

function shClose() {
  if (!sh.pk) return;
  sh.pk.pk.remove();
  sh.pk.back.remove();
  sh.pk.el.classList.remove('sh-active');
  sh.pk = null;
}

async function shChoose(value) {
  const { jid, field } = sh.pk;
  shClose();
  const m = cache[view];
  const it = shItems(m).find(x => x.jid === jid);
  const r = shRow(m, it);
  if ((field === 'category' && value === r.category) || (field === 'line' && (value || null) === r.line)
    || (field === 'comment' && value === r.comment)) return;
  const e = sh.edits[jid] || (sh.edits[jid] = {});
  const before = { ...e };
  const body = { id: jid };
  if (field === 'category') {
    const line = R.ids.budgets[value] ? value : null;     // its own line, when there is one by that name
    Object.assign(e, { category: value, line });
    Object.assign(body, { category: value, line: line ? R.ids.budgets[line] : '' });
    sh.touched.add(jid + ':line');
    if (value && !((R.sheet || {}).categories || []).includes(value) && !shPlanLine(value)) {
      (R.sheet = R.sheet || { categories: [] }).categories.push(value);
    }
  } else if (field === 'line') {
    e.line = value || null;
    body.line = value ? R.ids.budgets[value] : '';
  } else {
    // a comment is the charge's own line in its notes, "Comment: …"; the rest stays as it is
    Object.assign(body, { note: value ? 'Comment: ' + value : '', was: r.comment ? 'Comment: ' + r.comment : '' });
    e.comment = value;
  }
  e.st = 'saving';
  sh.touched.add(jid + ':' + field);
  sh.failed.delete(jid + ':' + field);
  sh.flash = { jid, field };
  paint();
  try {
    await post('tx', body);
    e.st = 'saved';
    shFresh();
  } catch (err) {
    sh.edits[jid] = before;
    if (!Object.keys(before).length) delete sh.edits[jid];
    sh.failed.add(jid + ':' + field);
  }
  paint();
}

/* after a change, ask for the numbers again until the new copy lands */
function shFresh() {
  if (R.months.some(x => x.key === view)) return waitForFresh(R.updated);
  const key = view, was = (cache[key] || {}).updated;
  let n = 0;
  const tick = async () => {
    const x = await load(key).catch(() => null);
    if (x && x.updated !== was) { absorb(x, false); if (view === key) paint(); return; }
    if (++n < 20) setTimeout(tick, 3000);
  };
  setTimeout(tick, 3000);
}

function shAfterPaint() {
  if (sh.flash) {
    const el = document.querySelector(`[data-sh-id="${sh.flash.jid}"] [data-sh-edit="${sh.flash.field}"]`);
    if (el) el.classList.add('sh-flash');
    sh.flash = null;
  }
}

document.addEventListener('click', e => {
  const t = e.target;
  if (sh.pk) {
    const b = t.closest('.sh-pk-list button');
    if (b) return shChoose(b.dataset.v);
    if (t.closest('[data-sh-ok]')) return shChoose(sh.pk.pk.querySelector('input').value.replace(/\s+/g, ' ').trim());
    if (t.closest('[data-sh-cancel]') || t.closest('.sh-pk-back')) return shClose();
    if (t.closest('.sh-pk')) return;
    shClose();
  }
  const v = t.closest('[data-sheet]');
  if (v) {
    sheet = v.dataset.sheet === '1';
    try { localStorage.setItem('record.view', sheet ? 'sheet' : 'record'); } catch (err) { /* this device only */ }
    planning = null;
    return paint();
  }
  const ed = t.closest('[data-sh-edit]');
  if (ed) return shOpen(ed);
  const tb = t.closest('[data-sh-tab]');
  if (tb) { sh.tab = /^\d+$/.test(tb.dataset.shTab) ? Number(tb.dataset.shTab) : tb.dataset.shTab; return paint(); }
  const f = t.closest('[data-sh-filt]');
  if (f) { sh.filt = f.dataset.shFilt; return paint(); }
  if (t.closest('[data-sh-unline]')) { sh.line = null; return paint(); }
  const s = t.closest('[data-sh-sort]');
  if (s) {
    if (sh.sortBy === s.dataset.shSort) sh.sortDir = -sh.sortDir;
    else { sh.sortBy = s.dataset.shSort; sh.sortDir = sh.sortBy === 'amount' ? -1 : 1; }
    return paint();
  }
  const wk = t.closest('td.sh-wk');
  if (wk && wk.textContent.trim()) { sh.line = wk.dataset.shLine; sh.tab = Number(wk.dataset.shWeek); sh.filt = 'all'; return paint(); }
  const nm = t.closest('td.sh-name');
  if (nm) { sh.line = nm.dataset.shLine; sh.tab = 'month'; sh.filt = 'all'; return paint(); }
});
document.addEventListener('input', e => {
  if (e.target.id === 'shq') {
    sh.q = e.target.value;
    const pos = e.target.selectionStart;
    paint();
    const q = document.getElementById('shq');
    if (q) { q.focus(); q.setSelectionRange(pos, pos); }
  } else if (sh.pk && sh.pk.field !== 'comment' && e.target.closest('.sh-pk') && e.target.tagName === 'INPUT') {
    shFill(e.target.value);
  }
});
document.addEventListener('keydown', e => {
  if (!sh.pk) return;
  if (e.key === 'Escape') return shClose();
  if (sh.pk.field === 'comment') {
    if (e.key === 'Enter') { e.preventDefault(); shChoose(sh.pk.pk.querySelector('input').value.replace(/\s+/g, ' ').trim()); }
    return;
  }
  const bs = sh.pk.pk.querySelectorAll('.sh-pk-list button');
  if (e.key === 'ArrowDown') { e.preventDefault(); sh.pk.hl = Math.min(bs.length - 1, sh.pk.hl + 1); shHl(); }
  else if (e.key === 'ArrowUp') { e.preventDefault(); sh.pk.hl = Math.max(0, sh.pk.hl - 1); shHl(); }
  else if (e.key === 'Enter' && bs[sh.pk.hl]) { e.preventDefault(); shChoose(bs[sh.pk.hl].dataset.v); }
});
document.addEventListener('scroll', e => {
  if (sh.pk && !sh.still && !shNarrow() && !(e.target.closest && e.target.closest('.sh-pk'))) shClose();
}, true);
matchMedia('(max-width: 575.98px)').addEventListener('change', () => { shClose(); if (sheet) paint(); });

/* record/data is now; record/data?month=YYYY-MM any other month, worked out
   on the host the first time it is asked for (202 until it is there) */
async function load(key = '') {
  const headers = auth ? { Accept: 'application/json', Authorization: auth } : { Accept: 'application/json' };
  const r = await fetch(base + 'data' + (key ? '?month=' + key : ''), { headers, credentials: 'same-origin' });
  if (r.status === 202) return null;
  if (!r.ok) throw new Error('HTTP ' + r.status);
  return r.json();
}

/* a month's own copy wins for that month; the month before it, which comes
   along, only fills a gap */
function absorb(x, main) {
  x.months.forEach((m, i) => {
    if (main || i === 0 || !cache[m.key]) cache[m.key] = Object.assign(m, { updated: x.updated });
  });
  Object.assign(limits, (x.ids || {}).limits || {});
}

function showMonth(key) {
  view = key;
  sel = null;
  history.replaceState(null, '', key === R.months[0].key ? here : here + '?month=' + key);
  paint();
  if (R.months.some(m => m.key === key)) return;     // came with the main copy
  asking = key;
  let n = 0;
  const tick = async () => {
    if (asking !== key) return;
    const x = await load(key).catch(() => null);
    if (x && asking === key) {
      const was = (cache[key] || {}).updated;
      absorb(x, false);
      if (view === key && x.updated !== was) paint();
      if (x.age !== null && x.age <= 600) return;    // fresh enough
    }
    if (++n < 30) setTimeout(tick, 2000);
  };
  tick();
}

/* An old copy is drawn at once; the host is asked for a fresh one and the
   page redraws when it lands, usually within a few seconds. */
function waitForFresh(was) {
  if (freshening) { freshAgain = true; return; }
  freshening = true;
  let n = 0;
  const stop = () => {
    clearInterval(t);
    freshening = false;
    if (freshAgain) { freshAgain = false; waitForFresh(R.updated); }
  };
  const t = setInterval(async () => {
    n++;
    try {
      const x = await load();
      if (x && x.updated !== was) { R = x; absorb(x, true); paint(); stop(); }
    } catch (e) { /* keep the copy on screen */ }
    if (n >= 20) stop();
  }, 3000);
}

async function post(path, body) {
  const headers = { 'Content-Type': 'application/json', Accept: 'application/json' };
  if (auth) headers.Authorization = auth;
  else headers['X-CSRF-TOKEN'] = csrf();
  const r = await fetch(base + path, {
    method: 'POST', credentials: 'same-origin', headers,
    body: JSON.stringify(body),
  });
  if (!r.ok) throw new Error('HTTP ' + r.status);
  return r.json();
}

async function save(patch) {
  R.state = (await post('state', patch)).state;
  paint();
}

document.addEventListener('click', e => {
  const t = e.target;
  const day = t.closest('[data-day]');
  if (day) { sel = day.dataset.day; return paint(); }
  const go = t.closest('[data-goto]');
  if (go && !e.metaKey && !e.ctrlKey) {
    e.preventDefault();
    planning = null;
    showMonth(go.dataset.goto);
    return window.scrollTo({ top: 0 });
  }
  const step = t.closest('[data-step]');
  if (step) {
    const keys = monthKeys(), next = keys[keys.indexOf(view) + Number(step.dataset.step)];
    planning = null;
    if (next) showMonth(next);
    return;
  }
  const lk = t.closest('[data-look]');
  if (lk) { lk.disabled = true; save({ look: lk.dataset.look }).catch(() => { lk.disabled = false; }); return; }
  const pl = t.closest('[data-plan]');
  if (pl) {
    planning = pl.dataset.plan || null;
    if (planning && view !== R.months[0].key) return showMonth(R.months[0].key);
    return paint();
  }
  if (t.closest('#planSave')) savePlan(t.closest('#planSave'));
});
document.addEventListener('change', e => {
  if (e.target.id === 'month') { planning = null; return showMonth(e.target.value); }
  if (e.target.id === 'since' && e.target.value) save({ since: e.target.value }).catch(() => {});
  if (e.target.id === 'ofMonth' || e.target.id === 'ofTax') {
    save({ offer: { hours: document.getElementById('ofMonth').value, tax: document.getElementById('ofTax').value } }).catch(() => {});
  }
});
document.addEventListener('input', e => {
  if (e.target.matches('[data-pl], [data-ideal]')) return planTotals();
  if (e.target.matches('[data-of]')) { offerIn[e.target.dataset.of] = e.target.value; return offerCalc(); }
  if (e.target.id === 'ofMonth' || e.target.id === 'ofTax') return offerCalc();
  if (e.target.id !== 'pay') return;
  pay = Math.max(0, Number(e.target.value) || 0);
  document.querySelectorAll('[data-share]').forEach(td => {
    td.textContent = pay ? usd(Math.max(1, pay * Number(td.dataset.share))) : '—';
  });
  document.getElementById('paytot').textContent = pay ? usd(pay) : '';
});

load().then(x => {
  R = x;
  absorb(x, true);
  pay = R.debts.repayment_line || 0;
  const asked = new URLSearchParams(location.search).get('month');
  showMonth(monthKeys().includes(asked) ? asked : R.months[0].key);
  if (R.age === null || R.age > 60) waitForFresh(R.updated);
  // the app calls this when it comes back from a transaction, which may have changed
  if (inApp) window.recordRefresh = () => waitForFresh(R.updated);
}).catch(() => {
  document.getElementById('record-page').innerHTML = '<div class="alert alert-warning">No data yet</div>';
});
})();
@endverbatim
</script>
@endsection
