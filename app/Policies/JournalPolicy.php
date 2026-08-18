<?php
namespace App\Policies;
use App\Models\HasJournal; use App\Models\User;
class JournalPolicy {
    public function view(User $u,HasJournal $m){return $u->isAdmin()||$m->user_id===$u->id;}
    public function update(User $u,HasJournal $m){return $this->view($u,$m);}
    public function delete(User $u,HasJournal $m){return $this->view($u,$m);}
}
