@inject('link', 'App\Helpers\LinkHelper')
<ul>
  @foreach($auditTrail as $a)
  <li>
    <span class="date">{{ $a['created_at'] }}</span>
    <a href="{{ $link->author($a['account_id'], $a['account_name']) }}">{{ $a['account_name'] }}</a>
    {!! $a['message'] . ($a['entity'] === null ? '.' : ' '. $a['entity'].'.') !!}
  </li>
  @endforeach
</ul>
