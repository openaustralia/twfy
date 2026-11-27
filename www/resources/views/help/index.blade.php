@extends('layout.legacy')

@section('content')
<x-help.page title="Answers to your questions">
<nav aria-label="Questions on this page">
<ul>
    <li><a href="#missing">Is this the whole of Hansard?</a></li>
    <li><a href="#glossary">What do the terms used in debates mean?</a></li>
    <li><a href="#privacy">What is your Privacy Policy?</a></li>
    <li><a href="#cookie">What is your Cookie Policy?</a></li>
    <li><a href="#extreme">What's that Plausible script I spy in your page source?</a></li>
    <li><a href="#rss">What is RSS?</a></li>
    <li><a href="#votingrecord">How is the voting record decided?</a></li>
    <li><a href="#numbers">Why should I read in more depth than just the numbers?</a></li>
    <li><a href="#api">Do you have the data as a spreadsheet file, XML or in an API?</a></li>
    <li><a href="#regmem">What is the Register of Interests and how can I use it?</a></li>
    <li><a href="#regmem-links">Why are there two Register of Interests links for some MPs?</a></li>
</ul>
</nav>

<h2 id="missing">Is this the whole of Hansard?</h2>
<p>Not quite. This is everything in the Hansard for the House of Representatives and the Senate excluding
    written questions, petitions,
    and the divisions (voting) and so far goes back to the beginning of 2006. It also does not include
    committees. Think of what we've done
    thus far as a mere taster of what could be possible.
    If you want the complete, definitive record, go to the <a href="https://www.aph.gov.au/"
        title="Link to Australian Parliament website">Australian Parliament</a> site, and you might be able to
    find what you want.</p>

<h2 id="glossary">What do the terms used in debates mean?</h2>
<p>Parliament has its own jargon. Our <a href="/glossary/">glossary</a> explains it in plain words, and the
    terms it covers link to their definitions wherever they come up in a debate. If you have an account, you
    can <a href="/addterm/">suggest a term</a> too.</p>

<h2 id="privacy">What is your Privacy Policy?</h2>
<p>Our Privacy Policy is very simple:</p>
@include('help.partials.privacy-policy', ['stop' => '.'])

<h2 id="cookie">What is your Cookie Policy?</h2>
<p>We use cookies to save you from having to repeatedly log in to the site, and also to remember your electoral
    division. The site will work with cookies disabled, but it won't be as good. </p>

<h2 id="extreme">What's that Plausible script I spy in your page source?</h2>
<p>We use <a href="https://plausible.io" rel="nofollow noreferrer noopener" target="_blank">Plausible<span class="sr-only"> (opens in a new tab)</span></a>, a privacy-friendly analytics tool, to track
    aggregated traffic through the website - which pages get visited, and how many people are visiting.
    Unlike Google Analytics, which we used to run, Plausible doesn't use cookies, doesn't track you
    across other websites, and doesn't store your IP address or build any profile of you. We only track
    usage data for one reason: to help us understand how we can make the site work better for you lot.</p>

<h2 id="rss">What is RSS?</h2>
<p>RSS files contain information about a list of things: diary entries, speeches, etc. and are formatted to be
    readable by computer programs, rather than humans. So what use are they? You can use a program called a news
    reader to store the locations of RSS feeds, and each time one is updated - with new diary entries or
    speeches - you can easily see what's new. It saves you visiting web pages on the off-chance anything new has
    appeared. Popular RSS readers are <a href="http://www.sharpreader.net/">Sharpreader</a> for Windows or <a
        href="http://ranchero.com/netnewswire/#lite">NetNewsWire Lite</a> for Macs. <a
        href="http://www.bloglines.com/">Bloglines</a> and <a href="http://www.google.com/reader">Google
        Reader</a> are online RSS readers.</p>

<h2 id="votingrecord">How is the voting record decided?</h2>
<p>The voting record is not affected by what MPs and Senators have said, only how they
    <strong>voted</strong> in relation to that topic in the house - i.e. "aye" or
    "no". Votes on each topic were examined, and strength of support determined
    based on these votes. Follow the "votes" link next to each topic for details.</p>

<h2 id="numbers">Why should I read in more depth than just the numbers?</h2>
<p>A few people have asked why we publish statistics on how often Representatives use
    alliterative phrases, such as "she sells seashells".</p>

<p>Simply put, we realise that data such as the number of debates spoken in Parliament
    means little in terms of a Representative's actual performance. Representatives do lots of useful
    things which we don't count yet, and some which we never could. Even when we
    do, a count doesn't measure the quality of a Representative's contribution.</p>

<p>On the UK site <a href="https://www.theyworkforyou.com">TheyWorkForYou</a>, they used to publish absolute
    rankings of, for
    instance, the most verbose MPs. Then, they were hearing from real MP's researchers who
    admitted to tabling questions to increase their boss' rankings. So, TheyWorkForYou
    became concerned about the use of these statistics</p>

<p>We've followed their lead by doing two things. We have silly statistics, to catch your attention.
    And we don't have absolute rankings. Instead of saying a Representative is exactly
    5th for giving out verbiage in the chamber, we just say that they are
    "well above average".</p>

<p>Our advice &mdash; when you're judging your Representative, read some of their speeches,
    check out their website, even go to a local meeting and ask them a question.
    Use OpenAustralia as a gateway, rather than a simple place to find a number
    measuring competence.</p>

<p>If you have other suggestions for useful metrics, send them to the <x-help.email>usual
        address</x-help.email>. We have a few ideas ourselves to keep you on your toes.</p>

<h2 id="api">Do you have the data as a spreadsheet file, XML or in an API?</h2>
<p>Yes. If you just need a spreadsheet of
    representatives, you'll find one on the right hand side of <a href="/mps">this page</a>. If you need a
    full-blown API (Application Programming Interface), which gives you the power to do almost anything with our
    data, <a href="/api">we have that too</a>. We also give you <a href="https://data.openaustralia.org.au">direct
        access to the XML data</a> which gets loaded into our database.</p>

<p>Please <x-help.email>mail us</x-help.email> if you want help
    working out how to use the data, or want to hire us to make something specific
    for you.</p>

<h2 id="regmem">What is the Register of Interests and how can I use it?</h2>
<p>The Register of Interests contains information of financial interests, stocks and shares held, gifts received
    over a certain value, and memberships of Clubs and Associations for Senators and Representatives. These are
    the things that have been considered to be a potential influence on their behaviour in Parliament.</p>

<p>Find out more at the <a
        href="https://www.aph.gov.au/Parliamentary_Business/Committees/Senate/Senators_Interests">Senate Standing
        Committee of Senators' Interests</a> and the <a
        href="https://www.aph.gov.au/Parliamentary_Business/Committees/House_of_Representatives_Committees?url=pmi/index.htm">Standing
        Committee of Privileges and Members' Interests</a>.</p>

<p>The SMH published <a
        href="https://web.archive.org/web/20130309030859/http://www.smh.com.au/national/political-interests">a
        searchable database</a> of the register in 2012. The project is now defunct and the archived copy should
    not be treated as current.</p>

<h2 id="regmem-links">Why are there two Register of Interests links for some MPs?</h2>
<p>In early 2009, OpenAustralia.org was <a
        href="https://www.oaf.org.au/2009/01/05/the-register-of-senators-interests-is-now-online-2/">the
        first to publish</a> the important Register of Interests online. We're happy to say that Australian
    Parliament House now publishes this register online themselves.</p>

<p>That's also why you'll see two links on some MP pages. One links to the latest entry from parliament house
    and the other links to the last update that was sent to us. You should only ever need the latest copy from
    Parliament house, but you never know so we've kept our (older) copy available too.</p>
</x-help.page>
@endsection
