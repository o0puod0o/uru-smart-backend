<?php
namespace App\Policies;
use App\Models\EntityFile; use App\Models\User;
class EntityFilePolicy {
    public function view(User $u,EntityFile $m){return $u->isAdmin()||$m->owner_user_id===$u->id;}
    public function delete(User $u,EntityFile $m){return $this->view($u,$m);}
}
