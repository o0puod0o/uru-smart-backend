<?php
namespace App\Policies;
use App\Models\Proposal; use App\Models\User;
class ProposalPolicy {
    public function view(User $u,Proposal $m){return $u->isAdmin()||$m->owner_user_id===$u->id;}
    public function update(User $u,Proposal $m){return $this->view($u,$m);}
    public function delete(User $u,Proposal $m){return $this->view($u,$m);}
}
