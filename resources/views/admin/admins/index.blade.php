@extends('varbox::layouts.admin.default')

@section('title', $title)

@section('content')
    <div class="row row-cards">
        <div class="col-lg-3">
            @permission('admins-add')
                @include('varbox::buttons.add', ['url' => route('admin.admins.create')])
            @endpermission

            @include('varbox::admin.admins._filter')
        </div>
        <div class="col-lg-9">
            @include('varbox::admin.admins._table')

            {!! $items->links('varbox::pagination.default', request()->query()) !!}
        </div>
    </div>
@endsection
