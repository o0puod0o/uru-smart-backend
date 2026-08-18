<?php
namespace App\Policies;
use App\Models\Report; use App\Models\User;
class ReportPolicy {
    public function view(User $u,Report $m){return $u->isAdmin()||$m->owner_user_id===$u->id;}
    public function update(User $u,Report $m){return $this->view($u,$m);}
    public function delete(User $u,Report $m){return $this->view($u,$m);}
}
