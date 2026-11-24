{{-- The title of an admin page, with the icon its menu item has. AdminController::page() draws it. --}}
<h3 class="mb-4 mt-0 flex items-center gap-3">@if ($icon)<x-admin.icon :name="$icon" :size="28" />@endif{{ $title }}</h3>
