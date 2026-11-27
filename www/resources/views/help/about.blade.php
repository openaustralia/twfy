@extends('layout.legacy')

@section('content')
<x-help.page title="About us">
<p>For all its faults and foibles, our democracy is a profound gift from previous generations.
    Yet most people don't know the name of their representative, let alone what they
    do or say in their name.</p>

<p>We aim to help bridge this growing democratic disconnect, in the belief that there is
    little wrong with Parliament that a healthy mixture of transparency and public engagement won't fix.</p>

<p>Hence this library.</p>

<h2>Who runs this site?</h2>

<p>OpenAustralia is a library collection, on the web, run by a charity, the <a href="https://www.oaf.org.au">OpenAustralia
        Foundation</a>.
    OpenAustralia Foundation is a public digital online library.
    We owe a great debt to a UK charity, mySociety, which built <a
        href="http://theyworkforyou.com/">theyworkforyou.com</a> which this site is based on.
</p>

<p>The Australian Hansard is
    <a href="https://www.aph.gov.au/Help/Disclaimer_Privacy_Copyright">Copyright Commonwealth of Australia</a>,
    and is used with permission. Images of politicians are <a href="https://www.aph.gov.au/Help/Disclaimer_Privacy_Copyright">Copyright Commonwealth of
        Australia</a> and administered by <a href="mailto:auspic@aph.gov.au">AUSPIC</a>.
</p>
<p>If you're technically minded, we make all the
    <a href="https://software.openaustralia.org/">OpenAustralia source code</a>
    freely available. Do let us know if you want to help!
</p>

<h2 id="history">History</h2>

<p>Back in 2004 Matthew Landauer and Katherine Szuminska were lucky enough to accidentally find themselves at the launch
    of
    the UK site <a href="https://www.theyworkforyou.com/">TheyWorkForYou.com</a> at a nerdy techno conference, NotCon 2004.
    The talk
    inspired them hugely and they left with a desire to make this happen in Australia too. It took a few years, other
    people
    came on board to help and here you can see the fruits of their labour.</p>

<p>The project was kick-started in November 2007 when Matthew, supported by <a href="https://www.rsp.com.au/">Rising Sun
        Pictures</a>,
    was given the opportunity to take a week of paid charity leave to develop the first, very rough, prototype of
    OpenAustralia.</p>

<h2>Volunteers on OpenAustralia.org</h2>

<ul class="!m-0 !grid !list-none !gap-3 !p-0 sm:!grid-cols-2">
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/b22adbe107ae92088a105b551354d487?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Tim Ansell</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/039a87be2d3843f64170f5e625252e84?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Nathanael Boehm</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/9f24adfa971912d72f72c1396ef63351?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Lachie Cox</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/b30d37e67e4c4584a71d977763651513?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Henare Degan</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/839aef22d6c767b07a630ceb9ac26793?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Simon Elvery</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/011c7cbc5af9c3c81b12b9c173a9db32?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Ray Haleblian</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/8b4c4881fbc0495bb58f4bc7b59fd960?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Matt Joyce</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/a1ed877d79337034f8e1bb46f2dad8e3?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Daniel Kinsman</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/5a600494d91ea4223e7256989155f687?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Matthew Landauer</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/4dde9fa837cdd695790518e140efbf7d?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Rob Manson</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/d25d7166e80639478ef1811788b8e61e?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Bruno Mattarollo</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/0a6ceb73d181645bcaff2c21e8fe0e85?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Wade Millican</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/6a5ba0842a531163425d66839569a68?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Matthew Panetta</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/b6d632e6f84a8b522199a91d75b7eb39?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Sarah Stokely</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/87bb46366f13106c2deebf655af73b38?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Katherine Szuminska</li>
<li class="!m-0 flex items-center gap-3"><img src="https://www.gravatar.com/avatar/3c1983a3371799ba1a78606dc62655db?s=32&amp;d=identicon&amp;r=g" width="32" height="32" alt="" loading="lazy" referrerpolicy="no-referrer" class="h-8 w-8 rounded">Justin Wells</li>
</ul>

<p><em>They did all this in their spare time, because they thought it was worth doing.
        And if it's worth doing, you might as well try to do it well. </em></p>

<h2>Volunteers on TheyWorkForYou.com</h2>

<ul>
<li>Richard Allan</li>
<li>Martin Belam</li>
<li>James Crabtree</li>
<li>James Cronin</li>
<li>Stephen Dunn</li>
<li>Yoz Grahame</li>
<li>Phil Gyford</li>
<li>David Heath</li>
<li>Francis Irving</li>
<li>Ben Laurie</li>
<li>Tom Loosemore</li>
<li>Stefan Magdalinski</li>
<li>Dorian McFarland</li>
<li>Anno Mitchell</li>
<li>Danny O'Brien</li>
<li>Sam Smith</li>
<li>Matthew Somerville</li>
<li>Tom Steinberg</li>
<li>Stuart Tily</li>
<li>Julian Todd</li>
<li>Denise Wilton</li>
</ul>
</x-help.page>
@endsection
