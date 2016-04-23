@extends('multilang::app')

@section('content')
    <nav class="navbar navbar-default">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="{{url(config('multilang.text-route.route'))}}">
                Text Management
                </a>
                <form class="navbar-form navbar-left " role="search">
                    <div class="form-group">
                        <select name="lang" class="form-control">
                            @foreach (config('multilang.locales') as $key  =>  $locale)
                                <option
                                    value="{{$key}}"
                                    @if ($key == $options['lang'])
                                    selected="selected"
                                    @endif
                                >
                                {{$locale['name']}}</option>
                            @endforeach
                        </select>
                        <input type="text" name="keyword" value="{{$options['keyword']}}" class="form-control" placeholder="Search">
                    </div>
                    <button type="submit" class="btn btn-primary" name="search">Submit</button>
                    @if(isset($options['search']))
                    <button type="button" onclick="location.href='{{url(config('multilang.text-route.route'))}}'" class="btn btn-danger">Reset</button>
                    @endif
                </form>
            </div>
        </div>
    </nav>
    @if (count(session('errors')) > 0)
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{lang_url(config('multilang.text-route.route').'/save')}}" method="POST">
        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th class="col-md-4">Key</th>
                    <th class="col-md-6">Value</th>
                    <th class="col-md-2">Lang</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($texts as $text)
                <tr>
                    <td> {{$text->key}} </td>
                    <td>
                        <input
                            type="text"
                            class="form-control"
                            name="texts[{{$options['lang']}}][{{$text->key}}]"
                            value="{{$text->value}}"
                        >
                    </td>
                    <td> {{$text->lang}} </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="bg-warning text-danger text-center">Texts not found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <hr>
        <button type="submit" class="btn btn-success pull-right">Save</button>
    </form>

@endsection
