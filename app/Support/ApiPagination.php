<?php
namespace App\Support;
class ApiPagination { public static function response($p,$data){return response()->json(['data'=>$data,'meta'=>['current_page'=>$p->currentPage(),'per_page'=>$p->perPage(),'total'=>$p->total(),'last_page'=>$p->lastPage()]]);} }
