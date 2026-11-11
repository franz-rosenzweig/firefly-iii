# Record

This fork of Firefly III adds one page, **Record**, the first item under *Financial control* in the sidebar. It is the Spending Record and Spending Plan the way Jerrold Mundis lays them out in *How to Get Out of Debt, Stay Out of Debt & Live Prosperously*, with a few tools from his *Earn What You Deserve*. Nothing else in Firefly changes.

The method is entirely his. The Spending Record, the Spending Plan, the monthly sit-down, the contingency fund, the patient way of paying debts back, and on the earning side the offer check and looking at what's left per hour instead of the gross, all come from those two books. This page only does the arithmetic and keeps the pages, so the books are the REAL thing. If it's useful to you, please get them and read them.

The page shows the month by calendar weeks (1–7, 8–14, 15–21, 22–end), each day coloured by how its spending sat against the plan: green inside it, amber outside it, red past a line. Under that is every budget line with its Actual, Plan and (+) or (−). A **Plan** button opens the monthly sit-down, where each line gets its amount for this month or next, measured against recent earnings. Further down are the debts with their Reduction Record, earnings by month, client and year, and an offer check that weighs a job's pay per hour, after fees and tax, against what is needed per hour. Warnings at the top catch overdraft and late fees, buy-now-pay-later charges and a regular client gone quiet. Almost everything links to its Firefly page.

Next to the month there is a switch between the Record and the **Sheet**, which is the same month as a spreadsheet, the way the workbook's daily pages were kept. Each charge is a row, grouped by day with the day's colour, with its Date, Description, Amount, Category, Line, Account and Note. Tabs along the bottom open each week, the whole month, and Lines, the workbook's monthly page with every line by week, where a number opens the charges behind it. A charge's category and line are picked from a list that shows what is left in each line (typing a name that isn't there makes a new category). A Comment of your own can be typed in; it is kept in the charge's notes as a line starting "Comment:", and the rest of the notes show beside it as they are. Each change goes to Firefly as soon as it is made. A charge with no category, or with a category whose own line isn't the one it is on, is marked, and those can be shown on their own.

## What it adds

- `app/Http/Controllers/RecordController.php` serves the page, its numbers, the saving of the page's own settings, the saving of the month's Spending Plan, and the Sheet's changes to a charge.
- `resources/views/record/index.blade.php` is the page, and `resources/views/record/bare.blade.php` is the same page without Firefly's header and sidebar, for the phone app.
- `resources/views/components/layout/sidebar.blade.php` gets the link.
- `routes/web.php` gets `record`, `record/data`, `record/state`, `record/plan` and `record/tx`, behind the normal web login. `routes/api.php` gets the same under `api/v1/record/` (`app` in place of the page), behind an API token like every other Firefly API call.

The page writes to Firefly in three places only. Its settings (the day the count starts from, the days marked as looked, an Ideal Spending Plan, and what the offer check measures against) are the user preference `record_state`. The Plan's Save goes through Firefly's own budget code: a line with an auto-budget gets the amount as its auto-budget amount and leaves next month for Firefly to open on the 1st, so a rollover line keeps carrying its leftover; a line without one just gets the month's budget limit. And a change in the Sheet (`record/tx`) sets one withdrawal's category, budget or notes through Firefly's own journal update, one split at a time and without running the rules. A note is changed in place, so anything else in that charge's notes stays. After each change the page leaves `want-tx-<journal id>` in `/var/www/record/run/`, for a host that keeps hand-made changes of its own, since a pending charge is often replaced by a new one when it settles.

## Where the numbers come from

Firefly does no arithmetic for this page. It reads `/var/www/record/data/record.json` for this month and last, and `/var/www/record/data/months/YYYY-MM.json` for any other month. A script outside Firefly writes those files from Firefly's API. It is not part of this fork, because it is built around one person's accounts, budget lines and income. When a file is missing or old, the page leaves a note in `/var/www/record/run/` (`want`, or `want-YYYY-MM` for a month), and something on the host is expected to notice it, write the file and remove the note. Meanwhile the page shows what it has and redraws when the new copy lands.

## In the phone app

[Shefa](https://github.com/franz-rosenzweig/shefa), an Android app built on Waterfly III, shows this page as its Record tab. It opens `api/v1/record/app` with the token it already logs in with, and also puts the token after the `#` of that address. The page reads it from there, clears it from the address and sends it with every request. A charge opens as a Waterfly transaction, and other links open in the browser.

## How this branch is kept

The `record` branch is a Firefly release tag plus one commit holding everything above. For a new Firefly release, the commit is made again on top of the new tag. On the stock Docker image the same files can be copied over `fireflyiii/core`; that is how it actually runs, with a small build step that adds the sidebar link and the routes and stops if a Firefly update has moved what they hook into.

The method is Jerrold Mundis's, and this page wouldn't exist without his books. Firefly III is by James Cole and is licensed under the AGPL-3.0, as is this fork.
