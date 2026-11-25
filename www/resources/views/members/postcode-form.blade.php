<h1 class="!m-0 !mb-4 !block !leading-tight text-2xl font-semibold !text-slate-900">Find out about your Representative</h1>
<form action="{{ WEBPATH }}mp/" method="get">
<p class="!mx-0">
<label for="pc"><strong>Enter your Australian postcode:</strong></label>
<input type="text" id="pc" name="pc" value="{{ $postcode }}" maxlength="10" size="10" inputmode="numeric" autocomplete="postal-code">
<input type="submit" value="GO" class="submit"> <small>(e.g. 2340)</small>
</p>
<input type="hidden" name="ch" value="t">
</form>
