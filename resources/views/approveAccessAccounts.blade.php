@extends('layouts.general_layout')

@section('title', 'Approve Access to Accounts')

@section('sidebar')
    @parent
        <li class="active"><a href="{{route('requestsForAccess')}}">Account Access Requests</a></li>
        <li><a href="{{ route('execMRF') }}">Material Requisition Forms</a></li>
        <li><a class="subheader">Reports</a></li>
        <li><a href="{{ route('accountsActivityPA') }}"> Accounts Activity </a></li>
        <li><a href="{{ route('transactionsToday') }}"> Transactions Today </a></li>
        <li><a href="{{ route('budgetVariance') }}"> Budget Variance </a></li>
@endsection

@section('content')
    <div class="col s8 offset-s2 white z-depth-2" style="padding: 25px">
        <h3> Requests for Access </h3>
        @if($primary->isEmpty() && $secondary->isEmpty() && $tertiary->isEmpty())
            <p class="center">There are no pending requests</p>

        @else
            <table class="bordered highlight">
                <thead>
                <tr>
                    <th>Account Name</th>
                    <th>Requestee</th>
                    <th>Explanation</th>
                </tr>
                </thead>
                <tbody>
                @if($primary != null)
                    @foreach($primary as $p)
                        <tr>
                            <td>{{ $p->account_name }}</td>
                            <td>{{ $p->user_name }}</td>
                            <td>{{ $p->explanation }}</td>
                            <td class="right">
                                <form action="{{ route('respondRequest') }}" method="POST">
                                    {{ csrf_field() }}
                                    <input type="hidden" value="{{ $p->user_name }}" name="owner">
                                    <input type="hidden" value="{{ $p->account_name }}" name="account">
                                    <input type="hidden" value="p-{{ $p->id }}" name="id">
                                    <button class="waves-effect waves-light btn green darken-3"
                                            type="submit" value="Approve" name="submit">
                                        <i class="material-icons">check</i>
                                    </button>
                                    <button class="waves-effect waves-light btn red darken-2"
                                            type="submit" value="Deny" name="submit">
                                        <i class="material-icons">close</i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @endif

                @if($secondary != null)
                    @foreach($secondary as $s)
                        <tr>
                            <td>{{ $s->pa_name }}: {{ $s->account_name }}</td>
                            <td>{{ $s->user_name }}</td>
                            <td>{{ $s->explanation }}</td>
                            <td class="right">
                                <form action="{{ route('respondRequest') }}" method="POST">
                                    {{ csrf_field() }}
                                    <input type="hidden" value="{{ $s->user_name }}" name="owner">
                                    <input type="hidden" value="{{ $s->account_name }}" name="account">
                                    <input type="hidden" value="s-{{ $s->id }}" name="id">
                                    <button class="waves-effect waves-light btn green darken-3"
                                            type="submit" value="Approve" name="submit">
                                        <i class="material-icons">check</i>
                                    </button>
                                    <button class="waves-effect waves-light btn red darken-2"
                                            type="submit" value="Deny" name="submit">
                                        <i class="material-icons">close</i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @endif

                @if($tertiary != null)
                    @foreach($tertiary as $t)
                        <tr>
                            <td>{{ $t->pa_name }}: {{ $t->sa_name }}: {{ $t->account_name }}</td>
                            <td>{{ $t->user_name }}</td>
                            <td>{{ $t->explanation }}</td>
                            <td class="right">
                                <form action="{{ route('respondRequest') }}" method="POST">
                                    {{ csrf_field() }}
                                    <input type="hidden" value="{{ $t->user_name }}" name="owner">
                                    <input type="hidden" value="{{ $t->account_name }}" name="account">
                                    <input type="hidden" value="t-{{ $t->id }}" name="id">
                                    <button class="waves-effect waves-light btn green darken-3"
                                            type="submit" value="Approve" name="submit">
                                        <i class="material-icons">check</i>
                                    </button>
                                    <button class="waves-effect waves-light btn red darken-2"
                                            type="submit" value="Deny" name="submit">
                                        <i class="material-icons">close</i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        @endif
    </div>
@endsection

<script>
    @section('script')
        @if(session()->has('message'))
            Materialize.toast("{{session('message')}}", 4000);
        @endif
    @endsection
</script>