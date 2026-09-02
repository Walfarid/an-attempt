---
paths:
  - 'compose.prod.yaml, routes/console.php, app/Models/PageView.php, app/Models/Click.php'
---

# Models

## Analytics rows prune at 90 days via daily model:prune; scheduler service in prod compose, task in dev
Analytics retention: PageView and Click are Prunable at 90 days (they have NO timestamps — prune on viewed_at/clicked_at). routes/console.php registers Schedule::command('model:prune', ['--model' => [PageView::class, Click::class]])->daily(). Production runs it via the `scheduler` compose service (php artisan schedule:work, mirrors queue). Dev parity = `task artisan:schedule_work` (dev compose.yaml has NO app container — the app runs natively on the host; never add one). Keep prod compose.yaml, routes/console.php, and the Taskfile in sync when changing the schedule. VM note: compose.prod.yaml must be scp'd to /opt/walfa/compose.yaml to deploy the scheduler service.
