@php
use App\Role;
use Illuminate\Support\Facades\DB;
$role = Role::find(request()->route('id'));
if (!is_null($role) && !Auth::user()->role->canInteract(Role::find(request()->route('id')))) return redirect()->route('settings')->with('error', 'You cannot interact with that role.')->send();
if (!is_null($role)) {
    $role_permissions = $role->permissions;
    $affected_users = DB::table('users')->where('role', $role->id)->count();
    $available_roles = $role->getRolesAvailable(Auth::user()->role);
}
@endphp
@extends('layouts.default')
@section('content')
<h2 class="title has-text-weight-bold">{{ is_null($role) ? 'Create' : 'Edit' }} Role</h2>
@if(!is_null($role)) <h4 class="subtitle"><strong>Role:</strong> {{ $role->name }}</h4>@endif
<div class="columns">
    <div class="column">
        <div class="box">
            @include('includes.messages')
            <form action="{{ is_null($role) ? route('settings_roles_new') : route('settings_roles_edit_form') }}" method="POST" id="role_form">
                @csrf
                <div class="field">
                    <label class="label">Name<sup style="color: red">*</sup></label>
                    <div class="control">
                        <input type="text" name="name" class="input" placeholder="Role Name" value="{{ $role->name ?? old('name') }}">
                    </div>
                </div>
                <div class="field">
                    <label class="label">Order<sup style="color: red">*</sup></label>
                    <div class="control">
                        <input type="number" name="order" class="input" placeholder="Role Order" min="1" value="{{ $role->order ?? old('order') }}">
                    </div>
                </div>
                <div class="field">
                    <div class="control">
                        <label class="checkbox label">
                            Staff
                            <input type="checkbox" class="js-switch" name="staff" @if(isset($role->staff) && $role->staff) checked @endif>
                        </label>
                    </div>
                </div>
                <div class="field" id="superuser" style="display: none;">
                    <div class="control">
                        <label class="checkbox label">
                            Superuser
                            <input type="checkbox" class="js-switch" name="superuser" @if(isset($role->superuser) && $role->superuser) checked @endif>
                        </label>
                    </div>
                </div>
                <div class="control">
                    <button class="button is-success" type="submit">
                        <span class="icon is-small">
                            <i class="fas fa-save"></i>
                        </span>
                        <span>Save</span>
                    </button>
                    <a class="button is-outlined" href="{{ route('settings') }}">
                        <span>Cancel</span>
                    </a>
                    @if(!is_null($role))
                    <button class="button is-danger is-outlined is-pulled-right" type="button" onclick="openModal();">
                        <span>Delete</span>
                        <span class="icon is-small">
                            <i class="fas fa-times"></i>
                        </span>
                    </button>
                    @endif
                </div>
        </div>
    </div>
    <div class="column box is-8" id="permissions_box" style="visibility: hidden;">
        <h4 class="title has-text-weight-bold is-4">Permissions</h4>
        <hr>
        <h4 class="subtitle"><strong>Cashier</strong></h4>
        <div class="control">
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[cashier]" value="1" @if(!is_null($role) && (in_array('cashier', $role_permissions) || $role->superuser)) checked @endif>
                Create Orders
            </label>
        </div>
        <hr>
        <h4 class="subtitle"><strong>Users</strong>&nbsp;<input type="checkbox" class="permission" id="permission-users-checkbox" name="permissions[users]" onclick="updateSections();" value="1" @if(!is_null($role) && (in_array('users', $role_permissions) || $role->superuser)) checked @endif></h4>
        <div class="control" id="permission-users" style="display: none;">
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[users_list]" value="1" @if(!is_null($role) && (in_array('users_list', $role_permissions) || $role->superuser)) checked @endif>
                List Users
            </label>
            &nbsp;
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[users_view]" value="1" @if(!is_null($role) && (in_array('users_view', $role_permissions) || $role->superuser)) checked @endif>
                View User Information
            </label>
            &nbsp;
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[users_manage]" value="1" @if(!is_null($role) && (in_array('users_manage', $role_permissions) || $role->superuser)) checked @endif>
                Manage Users
            </label>
        </div>
        <hr>
        <h4 class="subtitle"><strong>Products</strong>&nbsp;<input type="checkbox" class="permission" id="permission-products-checkbox" name="permissions[products]" onclick="updateSections();" value="1" @if(!is_null($role) && (in_array('products', $role_permissions) || $role->superuser)) checked @endif></h4>
        <div class="control" id="permission-products" style="display: none;">
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[products_list]" value="1" @if(!is_null($role) && (in_array('products_list', $role_permissions) || $role->superuser)) checked @endif>
                List Products
            </label>
            &nbsp;
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[products_manage]" value="1" @if(!is_null($role) && (in_array('products_manage', $role_permissions) || $role->superuser)) checked @endif>
                Manage Products
            </label>
            &nbsp;
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[products_adjust]" value="1" @if(!is_null($role) && (in_array('products_adjust', $role_permissions) || $role->superuser)) checked @endif>
                Adjust Stock
            </label>
        </div>
        <hr>
        <h4 class="subtitle"><strong>Activities</strong>&nbsp;<input type="checkbox" class="permission" id="permission-activities-checkbox" name="permissions[activities]" onclick="updateSections();" value="1" @if(!is_null($role) && (in_array('activities', $role_permissions) || $role->superuser)) checked @endif></h4>
        <div class="control" id="permission-activities" style="display: none;">
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[activities_list]" value="1" @if(!is_null($role) && (in_array('activities_list', $role_permissions) || $role->superuser)) checked @endif>
                List Activities
            </label>
            &nbsp;
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[activities_manage]" value="1" @if(!is_null($role) && (in_array('activities_manage', $role_permissions) || $role->superuser)) checked @endif>
                Manage Activities
            </label>
        </div>
        <hr>
        <h4 class="subtitle"><strong>Orders</strong>&nbsp;<input type="checkbox" class="permission" id="permission-orders-checkbox" name="permissions[orders]" onclick="updateSections();" value="1" @if(!is_null($role) && (in_array('orders', $role_permissions) || $role->superuser)) checked @endif></h4>
        <div class="control" id="permission-orders" style="display: none;">
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[orders_list]" value="1" @if(!is_null($role) && (in_array('orders_list', $role_permissions) || $role->superuser)) checked @endif>
                List Orders
            </label>
            &nbsp;
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[orders_view]" value="1" @if(!is_null($role) && (in_array('orders_view', $role_permissions) || $role->superuser)) checked @endif>
                View Order Information
            </label>
            &nbsp;
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[orders_return]" value="1" @if(!is_null($role) && (in_array('orders_return', $role_permissions) || $role->superuser)) checked @endif>
                Return Orders
            </label>
        </div>
        <hr>
        <h4 class="subtitle"><strong>Statistics</strong></h4>
        <div class="control">
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[statistics]" value="1" @if(!is_null($role) && (in_array('statistics', $role_permissions) || $role->superuser)) checked @endif>
                View Statistics
            </label>
        </div>
        <hr>
        <h4 class="subtitle"><strong>Settings</strong>&nbsp;<input type="checkbox" class="permission" id="permission-settings-checkbox" name="permissions[settings]" onclick="updateSections();" value="1" @if(!is_null($role) && (in_array('settings', $role_permissions) || $role->superuser)) checked @endif></h4>
        <div class="control" id="permission-settings" style="display: none;">
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[settings_general]" value="1" @if(!is_null($role) && (in_array('settings_general', $role_permissions) || $role->superuser)) checked @endif>
                Manage General Settings
            </label>
            &nbsp;
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[settings_categories_manage]" value="1" @if(!is_null($role) && (in_array('settings_categories_manage', $role_permissions) || $role->superuser)) checked @endif>
                Manage Categories
            </label>
            &nbsp;
            <label class="checkbox">
                <input type="checkbox" class="permission" name="permissions[settings_roles_manage]" value="1" @if(!is_null($role) && (in_array('settings_roles_manage', $role_permissions) || $role->superuser)) checked @endif>
                Manage Roles
            </label>
        </div>
        </form>
    </div>
</div>

@if(!is_null($role))
<div class="modal">
    <div class="modal-background" onclick="closeModal();"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Confirmation</p>
        </header>
        <section class="modal-card-body">
            <p><strong>{{ $affected_users }}</strong>@if($affected_users > 1 || $affected_users == 0) users @else user @endif currently have this role.</p>
            <!-- 
                TODO: I broke something with the permissions js suddenly
                Rules:
                - Only roles which the current user can interact with 
                - If the deleted role is not a staff role, only other non-staff roles are shown 
                - If the role is a staff role, roles of any type are shown
            -->
            @if(!count($available_roles) > 0)
                <strong>No appropriate backup roles. Cannot delete.</strong>
            @else
                <form action="" id="deleteForm" method="GET">
                    @csrf
                    <input type="hidden" name="old_role" value="{{ $role->id }}">
                    @if ($affected_users >= 1)
                        <p>Please select a new role for them to be placed in:</p>
                        <div class="control">
                            <div class="select">
                                <select name="new_role" id="new_role" class="input" required>
                                    @foreach($available_roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                </form>
            @endif
        </section>
        <footer class="modal-card-foot">
            <button class="button is-success" type="submit" onclick="deleteData();" @if(!count($available_roles) > 0) disabled @endif>Confirm</button>
            <button class="button" onclick="closeModal();">Cancel</button>
        </footer>
    </div>
</div>
@endif

<script>
    $(document).ready(() => {
        updateStaffInfo($('input[type=checkbox][name=staff]').prop('checked'));
        if ($('input[type=checkbox][name=superuser]').prop('checked')) {
            updatePermissionSU(true);
        }
        updateSections();
    });

    $('input[type=checkbox][name=staff]').change(() => {
        updateStaffInfo($(this).prop('checked'))
    });

    $('input[type=checkbox][name=superuser]').change(() => {
        updatePermissionSU($(this).prop('checked'))
    });

    function updateStaffInfo(staff) {
        console.log(staff)
        if (staff) {
            $(document.getElementById('superuser')).show(200);
            $(document.getElementById('permissions_box')).css({
                opacity: 0.0,
                visibility: 'visible'
            }).animate({
                opacity: 1.0
            });
        } else {
            $(document.getElementById('superuser')).hide(200);
            $(document.getElementById('permissions_box')).css('visibility', 'hidden');
        }
    }

    function updatePermissionSU(superuser) {
        $('.permission').each(() => {
            const checkbox = $(this);
            checkbox.prop('checked', superuser);
            checkbox.prop('disabled', superuser)
        });
        updateSections();
    }

    function updateSections() {
        ['users', 'products', 'activities', 'orders', 'settings'].forEach(element => {
            if ($(`#permission-${element}-checkbox`).prop('checked')) $(`#permission-${element}`).show(200);
            else $(`#permission-${element}`).hide(200);
        });
    }

    $('form').submit(() => {
        $(':disabled').each(() => {
            $(this).removeAttr('disabled');
        });
    });

    const modal = document.querySelector('.modal');

    function openModal() {
        modal.classList.add('is-active');
    }

    function closeModal() {
        modal.classList.remove('is-active');
    }

    @if(!is_null($role))
        function deleteData() {
            var url = '{{ route("settings_roles_delete", ":id") }}';
            url = url.replace(':id', {{ $role->id }});
            $("#deleteForm").attr('action', url);
            $("#deleteForm").submit();
        }
    @endif

    const switches = document.getElementsByClassName("js-switch");
    for (var i = 0; i < switches.length; i++) {
        new Switchery(switches.item(i), {
            color: '#48C774',
            secondaryColor: '#F56D71'
        })
    }
</script>
@stop