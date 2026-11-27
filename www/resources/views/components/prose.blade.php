{{-- Text-heavy content, with its headings, paragraphs, lists and links styled here so a view can write plain HTML. A list or paragraph with its own class, or a link with its own style, keeps it. --}}
<div {{ $attributes->class([
    'text-base leading-[1.75] text-slate-800 [&>:first-child:not([class])]:!mt-0',
    '[&_h2]:!mx-0 [&_h2]:!mb-3 [&_h2]:!mt-10 [&_h2]:!block [&_h2]:!p-0 [&_h2]:!text-2xl [&_h2]:!font-bold [&_h2]:!leading-tight [&_h2]:!text-slate-900 [&_h2:first-child]:!mt-0',
    '[&_h3]:!mx-0 [&_h3]:!mb-2 [&_h3]:!mt-8 [&_h3]:!block [&_h3]:!p-0 [&_h3]:!text-xl [&_h3]:!font-bold [&_h3]:!leading-tight [&_h3]:!text-slate-900',
    '[&_p:not([class])]:!my-4 [&_p:not([class])]:!text-base [&_p:not([class])]:!leading-[1.75]',
    '[&_ul:not([class])]:!my-4 [&_ul:not([class])]:!list-disc [&_ul:not([class])]:!pl-6',
    '[&_nav>ul:not([class]):first-child]:!mt-0',
    '[&_ol:not([class])]:!my-4 [&_ol:not([class])]:!list-decimal [&_ol:not([class])]:!pl-6',
    '[&_li]:!my-1.5 [&_li]:!text-base',
    '[&_a:not([class]):not([style])]:!text-teal-700 [&_a:not([class]):not([style])]:!underline',
    '[&_code]:rounded [&_code]:bg-slate-100 [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:text-[15px] [&_code]:text-slate-900',
]) }}>{{ $slot }}</div>
