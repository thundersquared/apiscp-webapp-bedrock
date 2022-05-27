<button name="packageManager" data-note-target="#packageManager" type="submit" data-toggle="collapse"
    data-target="#packageManager" aria-controls="#packageManager" aria-expanded="false" class="mb-3 btn btn-secondary "
    name="packageManager" value="1">
    <i class="fa fa-gift"></i>
    Manage Packages
</button>

<div class="inline-block mb-3 btn btn-secondary" id="wpSsoPlaceholder">
    <i class="ui-ajax-loading ui-ajax-indicator"></i>
    {{ _('SSO Check') }}
</div>

<h5>Environments</h5>
<table class="table table-responsive">
    <thead>
        <th>Name</th>
        <th>Status</th>
    </thead>
    <tbody>
        @php
            $environments = \cmd('bedrock_get_environments', $app->getHostname(), $app->getPath());
        @endphp
        @foreach ($environments as $environment)
            <tr class="environment-row">
                <td>
                    <code>{{ $environment['name'] }}</code>
                </td>
                <td>
                    @if ($environment['status'] === true)
                        <button class="btn btn-disabled" disabled>Currently Enabled</button>
                    @else
                        <button class="btn btn-primary" bedrock-action="enabled-environment"
                            bedrock-environment="{{ $environment['name'] }}">Currently Enabled</button>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
