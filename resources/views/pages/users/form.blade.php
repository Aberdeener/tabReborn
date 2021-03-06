@extends('layouts.default', ['page' => 'users'])
@section('content')
<h2 class="title has-text-weight-bold">{{ is_null($user) ? 'Create' : 'Edit' }} User</h2>
@if(!is_null($user)) <h4 class="subtitle"><strong>User:</strong> {{ $user->full_name }} @permission('users_view')<a href="{{ route('users_view', $user->id) }}">(View)</a>@endpermission</h4>@endif
<div class="columns">
    <div class="column is-5">
        @include('includes.messages')
        <div class="box">
            <form action="/users/{{ is_null($user) ? 'new' : 'edit' }}" id="user_form" method="POST">
                @csrf
                <input type="hidden" name="id" id="user_id" value="{{ request()->route('id') }}">

                <div class="field">
                    <label class="label">Full Name<sup style="color: red">*</sup></label>
                    <div class="control">
                        <input type="text" name="full_name" class="input" required placeholder="Full Name" value="{{ $user->full_name ?? old('full_name') }}">
                    </div>
                </div>

                <div class="field">
                    <label class="label">Username</label>
                    <div class="control has-icons-left">
                        <span class="icon is-small is-left">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" name="username" class="input" placeholder="Username (Optional)" value="{{ $user->username ?? old('username') }}">
                    </div>
                </div>

                <div class="field">
                    <label class="label">Balance</label>
                    <div class="control has-icons-left">
                        <span class="icon is-small is-left">
                            <i class="fas fa-dollar-sign"></i>
                        </span>
                        <input type="number" step="0.01" name="balance" class="input" value="{{ isset($user->balance) ? number_format($user->balance, 2, ".", "") : number_format(old('balance'), 2, ".", "") }}">
                    </div>
                </div>

                <div class="field">
                    <label class="label">Role<sup style="color: red">*</sup></label>
                    <!-- TODO: some sort of blocking of changing their own role -->
                    <div class="control">
                        <div class="select" id="role_id">
                            <select name="role_id" class="input" required>
                                @foreach($available_roles as $role)
                                    <option value="{{ $role->id }}" data-staff="{{ $role->staff ? 1 : 0 }}" {{ (isset($user->role) && $user->role->id == $role->id) || old('role') == $role->id ? "selected" : "" }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div id="password_hidable" style="display: none;">
                    <div class="field">
                        <label class="label">{{ !is_null($user) ? 'Change ' : '' }}Password</label>
                        <div class="control has-icons-left">
                            <span class="icon is-small is-left">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" name="password" class="input" placeholder="Password" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="field">
                        <div class="control has-icons-left">
                            <span class="icon is-small is-left">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" name="password_confirmation" class="input" placeholder="Confirm Password" autocomplete="new-password">
                        </div>
                    </div>
                </div>

        </div>
    </div>

    <div class="column"></div>

    <div class="column is-5 box">
        <h4 class="title has-text-weight-bold is-4">Category Limits</h4>

        @foreach($categories as $category)
            <div class="field">
                <label class="label">{{ ucfirst($category['name']) }} Limit</label>
                <div class="control has-icons-left">
                    <span class="icon is-small is-left">
                        <i class="fas fa-dollar-sign"></i>
                    </span>
                    <input type="number" step="0.01" name="limit[{{ $category['name'] }}]" class="input" placeholder="Limit" value="{{ number_format($category['info']->limit_per, 2) }}">
                </div>
                <div class="control">
                    <label class="radio">
                        <input type="radio" name="duration[{{ $category['name'] }}]" value="0" @if($category['info']->duration == "day") checked @endif>
                        Day
                    </label>
                    <label class="radio">
                        <input type="radio" name="duration[{{ $category['name'] }}]" value="1" @if($category['info']->duration == "week") checked @endif>
                        Week
                    </label>
                </div>
            </div>
        @endforeach
    </div>
    </form>
    <div class="column is-2">
        <form>
            <div class="control">
                <button class="button is-success" type="submit" form="user_form">
                    <span class="icon is-small">
                        <i class="fas fa-check"></i>
                    </span>
                    <span>Submit</span>
                </button>
            </div>
        </form>
        <br>
        @if(!is_null($user))
        <div class="control">
            <form>
                <button class="button is-danger is-outlined" type="button" onclick="openModal();">
                    <span>Delete</span>
                    <span class="icon is-small">
                        <i class="fas fa-times"></i>
                    </span>
                </button>
            </form>
        </div>
        @endif
    </div>
</div>

@if(!is_null($user))
<div class="modal">
    <div class="modal-background" onclick="closeModal();"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Confirmation</p>
        </header>
        <section class="modal-card-body">
            <p>Are you sure you want to delete the user {{ $user->full_name }}?</p>
            <form action="" id="deleteForm" method="GET">
                @csrf
            </form>
        </section>
        <footer class="modal-card-foot">
            <button class="button is-success" type="submit" onclick="deleteData();">Confirm</button>
            <button class="button" onclick="closeModal();">Cancel</button>
        </footer>
    </div>
</div>
@endif

<script type="text/javascript">
    $(document).ready(function() {
        updatePassword($("option:selected", document.getElementById('role')).data('staff'));
    });

    $('select').on('change', function() {
        updatePassword($("option:selected", this).data('staff'))
    });

    function updatePassword(staff) {
        if (staff !== undefined) {
            let div = $('#password_hidable');
            if (staff) div.fadeIn(200);
            else div.fadeOut(200);
        }
    }

    @if(!is_null($user))
        const modal = document.querySelector('.modal');

        function openModal() {
            modal.classList.add('is-active');
        }

        function closeModal() {
            modal.classList.remove('is-active');
        }

        function deleteData() {
            var id = document.getElementById('user_id').value;
            var url = '{{ route("users_delete", ":id") }}';
            url = url.replace(':id', id);
            $("#deleteForm").attr('action', url);
            $("#deleteForm").submit();
        }
    @endif
</script>
@endsection