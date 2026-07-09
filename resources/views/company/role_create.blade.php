@extends('company.layout')

@section('content')

<h2>Create Role</h2>

<form method="POST" action="/company/role/store">
    @csrf

    <input type="text" name="name" placeholder="Role Name"><br><br>

    <h4>Permissions</h4>

    <label><input type="checkbox" name="permissions[]" value="create_users"> Create User</label><br>
    <label><input type="checkbox" name="permissions[]" value="edit_users"> Edit User</label><br>
    <label><input type="checkbox" name="permissions[]" value="delete_users"> Delete User</label><br>
    <label><input type="checkbox" name="permissions[]" value="view_users"> View User</label><br>

    <br>

    <button type="submit">Create Role</button>

</form>

@endsection