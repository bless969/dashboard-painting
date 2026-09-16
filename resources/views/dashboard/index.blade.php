@extends('layouts.app')

@section('title')
@if($view_aktif === 'dashboard') Dashboard — Line Painting
@elseif($view_aktif === 'machine-control') Machine Control — Line Painting
@elseif($view_aktif === 'chart') Chart — Line Painting
@elseif($view_aktif === 'riwayat') Riwayat — Line Painting
@elseif($view_aktif === 'detail') Detail Harian — Line Painting
@elseif($view_aktif === 'detail-shift') Detail Shift — Line Painting
@elseif($view_aktif === 'pengaturan') Pengaturan — Line Painting
@elseif($view_aktif === 'kwh-control') KWh Control — Painting
@else Dashboard — Line Painting
@endif
@endsection

@section('content')
    <!-- ===== MODULAR SPA VIEWS ===== -->
    @include('pages.dashboard')
    @include('pages.machine-control')
    @include('pages.chart')
    @include('pages.riwayat')
    @include('pages.detail')
    @include('pages.detail-shift')
    @include('pages.pengaturan')
    @include('pages.kwh-control')
@endsection
