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

<div id="bedrock-environments">
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
                            <button class="btn btn-primary" bedrock-action="enable_environment"
                                bedrock-environment="{{ $environment['name'] }}">Enable</button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script type="text/javascript">
    (function($) {
        const callbacks = {
            enable_environment: function(e) {
                console.log(e);
            }
        };

        // Select all actionable buttons
        const actionables = document.getElementById('bedrock-environments').querySelectorAll('[bedrock-action]');

        // Bind callback bv action
        actionables.forEach(function(element) {
            $(element).click(function(e) {
                e.preventDefault();
                e.stopPropagation();

                const callback = element.attr('bedrock-action');

                if (callback in callbacks) {
                    callbacks[callback](element);
                }
            });
        });
    })(jQuery);
</script>
