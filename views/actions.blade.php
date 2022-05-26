<button name="packageManager" data-note-target="#packageManager" type="submit" data-toggle="collapse" data-target="#packageManager"
        aria-controls="#packageManager" aria-expanded="false"
        class="mb-3 btn btn-secondary " name="packageManager" value="1">
	<i class="fa fa-gift"></i>
	Manage Packages
</button>

<div class="inline-block mb-3 btn btn-secondary" id="wpSsoPlaceholder">
	<i class="ui-ajax-loading ui-ajax-indicator"></i>
	{{ _("SSO Check") }}
</div>

<table class="table table-responsive">
	<thead>
	<th>
		Name
	</th>
	<th>
		Status
	</th>
	</thead>
    <tbody>
	@php
		$environments = \cmd('bedrock_get_environments', $app->getHostname(), $app->getPath());
	@endphp
	@foreach ($environments as $environment)
        <tr>
            <th colspan="5" class="bg-light">
                <h6 class="mb-0">{{ $asset['type'] }}</h6>
            </th>
        </tr>
        @endif
        <tr class="environment-row">
            <td>
                {{ $asset['name'] }}
            </td>
            <td>
                {{ $asset['status'] }}
            </td>
        </tr>
	@endforeach
	</tbody>
</table>
