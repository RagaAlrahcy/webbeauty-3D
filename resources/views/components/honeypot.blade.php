{{-- Spam trap: hidden from people and assistive tech; bots that fill it fail validation. --}}
<div class="absolute -left-[9999px] size-px overflow-hidden" aria-hidden="true">
    <label>
        {{ __('site.forms.honeypot') }}
        <input type="text" name="website" tabindex="-1" autocomplete="off" value="">
    </label>
</div>
