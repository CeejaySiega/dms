@extends('layouts/commonMaster')

@php
$containerNav = 'container-fluid';
$navbarDetached = 'navbar-detached';
@endphp

@section('layoutContent')
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        @include('layouts/sections/menu/verticalMenu')

        <div class="layout-page">
            @include('layouts/sections/navbar/navbar')

            <div class="content-wrapper">
                <div class="container-fluid flex-grow-1 container-p-y">
                    @yield('content')
                </div>

                @include('layouts/sections/footer/footer')
                <div class="content-backdrop fade"></div>
            </div>
        </div>

        <div class="layout-overlay layout-menu-toggle"></div>
        <div class="drag-target"></div>
    </div>
</div>
@endsection
