<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
 public function handle($request, Closure $next, ...$roles)
{

if(!auth()->check()){

return redirect('/login');

}

$userRole=(int)auth()->user()->role_id;

$allowedRoles=[];

foreach($roles as $role){

$allowedRoles=array_merge(
$allowedRoles,
explode('|',$role)
);

}

$allowedRoles=
array_map(
'intval',
$allowedRoles
);

if(!in_array($userRole,$allowedRoles)){

abort(403);

}

return $next($request);

}
}