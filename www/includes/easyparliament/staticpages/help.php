<?php

/**
 * @file
 */
?>
<style>
    .oa-help-faq {
        --faq-bg: #ebeccf;
        --faq-card: #ffffff;
        --faq-text: #333333;
        --faq-muted: #555555;
        --faq-accent: #b82e00;
        --faq-accent-soft: #fdf5f5;
        --faq-border: #cdcebc;
        --faq-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        position: relative;
        margin: 0 auto;
        max-width: 980px;
        padding: 1.5rem 1rem 2.5rem;
        color: var(--faq-text);
        font-family: Verdana, Arial, Geneva, Sans-serif;
        background: radial-gradient(circle at 90% 0%, #f8f1e5 0, rgba(248, 241, 229, 0) 40%),
            radial-gradient(circle at 10% 12%, #f5f5f5 0, rgba(245, 245, 245, 0) 38%),
            var(--faq-bg);
        border: 1px solid var(--faq-border);
        border-radius: 16px;
        box-shadow: var(--faq-shadow);
        overflow: hidden;
    }

    .oa-help-faq::before {
        content: "";
        position: absolute;
        top: -40px;
        right: -60px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(184, 46, 0, 0.08);
        pointer-events: none;
    }

    .oa-help-faq__hero {
        position: relative;
        margin-bottom: 1.5rem;
        padding: 1.4rem 1.4rem 1rem;
        background: linear-gradient(125deg, #fff 0%, #fbf5e9 55%, #fff 100%);
        border: 1px solid var(--faq-border);
        border-radius: 12px;
    }

    .oa-help-faq__eyebrow {
        margin: 0;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.16em;
        color: var(--faq-accent);
        font-weight: 700;
    }

    .oa-help-faq__title {
        display: block;
        margin: 0.35rem 0 0.45rem;
        font-size: clamp(1.6rem, 2.8vw, 2.2rem);
        line-height: 1.2;
        color: #b82e00;
    }

    .oa-help-faq__intro {
        margin: 0;
        max-width: 65ch;
        color: var(--faq-muted);
        font-size: 1.02rem;
        line-height: 1.5;
    }

    .oa-help-faq__index-title {
        margin: 0.4rem 0 0.8rem;
        font-size: 1.05rem;
        color: #ab4329;
    }

    .oa-help-faq__index {
        margin: 0 0 1.4rem;
        padding: 0;
        list-style: none;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.5rem;
    }

    .oa-help-faq__index li {
        margin: 0;
    }

    .oa-help-faq__index a {
        display: block;
        padding: 0.65rem 0.8rem;
        border-radius: 8px;
        border: 1px solid #d6c8b5;
        text-decoration: none;
        color: #00b;
        font-size: 0.94rem;
        line-height: 1.3;
        background: #fff;
        transition: transform 0.12s ease, background-color 0.2s ease, border-color 0.2s ease;
    }

    .oa-help-faq__index a:hover,
    .oa-help-faq__index a:focus {
        background: var(--faq-accent-soft);
        border-color: #eba668;
        transform: translateY(-1px);
    }

    .oa-help-faq dl {
        margin: 0;
    }

    .oa-help-faq dt {
        position: relative;
        margin: 0;
        padding: 1rem 1.1rem 0.2rem 2.5rem;
        font-size: 1.14rem;
        font-weight: 700;
        color: #ab4329;
        background: var(--faq-card);
        border: 1px solid var(--faq-border);
        border-bottom: 0;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }

    .oa-help-faq dt::before {
        content: "Q";
        position: absolute;
        left: 0.9rem;
        top: 0.95rem;
        width: 1.2rem;
        height: 1.2rem;
        border-radius: 50%;
        text-align: center;
        line-height: 1.2rem;
        font-size: 0.74rem;
        color: #fff;
        background: #eba668;
    }

    .oa-help-faq dd {
        margin: 0 0 1.1rem;
        padding: 0.2rem 1.1rem 1.05rem 2.5rem;
        background: var(--faq-card);
        border: 1px solid var(--faq-border);
        border-top: 0;
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
    }

    .oa-help-faq p {
        margin: 0.55rem 0;
        line-height: 1.58;
    }

    .oa-help-faq a {
        color: #00b;
    }

    .oa-help-faq a:visited {
        color: #505;
    }

    .oa-help-faq a:hover,
    .oa-help-faq a:focus {
        color: #00b;
    }

    @media (max-width: 760px) {
        .oa-help-faq {
            padding: 1rem 0.75rem 1.6rem;
            border-radius: 12px;
        }

        .oa-help-faq__hero {
            padding: 1rem 0.95rem;
        }

        .oa-help-faq__index {
            grid-template-columns: 1fr;
        }

        .oa-help-faq dt {
            padding: 0.95rem 0.8rem 0.2rem 2.1rem;
            font-size: 1.04rem;
        }

        .oa-help-faq dt::before {
            left: 0.68rem;
        }

        .oa-help-faq dd {
            padding: 0.2rem 0.8rem 0.95rem 2.1rem;
        }
    }
</style>

<section class="oa-help-faq">
    <header class="oa-help-faq__hero">
        <p class="oa-help-faq__eyebrow">Help and FAQ</p>
        <h1 class="oa-help-faq__title">Frequently asked questions</h1>
        <p class="oa-help-faq__intro">Quick answers about OpenAustralia.org, your data, and how to navigate key
            features of the site.</p>
    </header>

    <h2 class="oa-help-faq__index-title">Jump to a question</h2>
    <ul class="oa-help-faq__index">
        <li><a href="#moderation">How do you moderate comments?</a></li>
        <li><a href="#missing">Is this the whole of Hansard?</a></li>
        <li><a href="#privacy">What is your Privacy Policy?</a></li>
        <li><a href="#cookie">What is your Cookie Policy?</a></li>
        <li><a href="#rss">What is RSS?</a></li>
        <li><a href="#votingrecord">How is the voting record decided?</a></li>
        <li><a href="#numbers">Why should I read in more depth than just the numbers?</a></li>
        <li><a href="#api">Do you have the data as a spreadsheet file, XML or in an API?</a></li>
        <li><a href="#regmem">What is the Register of Interests and how can I use it?</a></li>
        <li><a href="#regmem-links">Why are there two Register of Interests links for some MPs?</a></li>
    </ul>

    <!-- start new faq entry -->
    <dl>

    <dt><a name="moderation"></a>How do you moderate comments?</dt>
    <dd>
        <p>Ideally, we won't. If everyone keeps to the <a href="<?php echo WEBPATH ?>houserules"
                title="link to House Rules">House Rules</a>, that is. But we're not naive enough to think that life
            online is that simple. OpenAustralia.org operates a 'reactive moderation' policy. We will only check whether
            a comment breaches our House Rules if someone lets us know of their concerns via the 'Report this Comment'
            link, which can be found next to every comment. If we decide that the comment has breached our House Rules,
            we will delete it and let the original author know via email. We will also give them opportunity to rephrase
            and resubmit their orginal comment. If we deem the comment to be legit, we'll leave it up, and email the
            complainant to let them know why. We will do our utmost to respond to reports of potential breaches of our
            House Rules within forty-eight hours, but please bear in mind that this service is run by a small charity,
            and sometimes it might take us slightly longer.</p>
    </dd>
    <!-- end old faq entry -->

    <!-- start new faq entry -->
    <dt><a name="missing"></a>Is this the whole of Hansard?</dt>
    <dd>
        <p>Not quite. This is everything in the Hansard for the House of Representatives and the Senate excluding
            written questions, petitions,
            and the divisions (voting) and so far goes back to the beginning of 2006. It also does not include
            committees. Think of what we've done
            thus far as a mere taster of what could be possible.
            If you want the complete, definitive record, go to the <a href="https://www.aph.gov.au/"
                title="Link to Australian Parliament website">Australian Parliament</a> site, and you might be able to
            find what you want.</p>
    </dd>
    <!-- end old faq entry -->

    <!-- start new faq entry -->
    <dt><a name="privacy"></a>What is your Privacy Policy?</dt>
    <dd>
        <p>Our Privacy Policy is very simple:</p>

        <p><strong>1.</strong> We guarantee we will not sell or distribute any personal information you share with us
        </p>
        <p><strong>2.</strong> We will not be sending you unsolicited email</p>
        <p><strong>3.</strong> We will gladly show you the personal data we store about you in order to run the website.
        </p>
        <!-- end old faq entry -->

        <!-- start new faq entry -->
    <dt><a name="cookie"></a>What is your Cookie Policy?</dt>
    <dd>
        <p>We use cookies to save you from having to repeatedly log in to the site, and also to remember your electoral
            division. The site will work with cookies disabled, but it won't be as good. </p>
    </dd>
    <!-- end old faq entry -->

    <!-- start new faq entry -->
    <dt><a name="rss"></a>What is RSS?</dt>
    <dd>
        <p>RSS files contain information about a list of things: diary entries, speeches, etc. and are formatted to be
            readable by computer programs, rather than humans. So what use are they? You can use a program called a news
            reader to store the locations of RSS feeds, and each time one is updated - with new diary entries or
            speeches - you can easily see what's new. It saves you visiting web pages on the off-chance anything new has
            appeared. Popular RSS readers are <a href="http://www.sharpreader.net/">Sharpreader</a> for Windows or <a
                href="http://ranchero.com/netnewswire/#lite">NetNewsWire Lite</a> for Macs. <a
                href="http://www.bloglines.com/">Bloglines</a> and <a href="http://www.google.com/reader">Google
                Reader</a> are online RSS readers.</p>
    </dd>

    <!-- end old faq entry -->

    <!-- start new faq entry-->
    <dt><a name="votingrecord"></a>How is the voting record decided?</dt>
    <dd>
        <p>The voting record is not affected by what MPs and Senators have said, only how they
            <strong>voted</strong> in relation to that topic in the house - i.e. "aye" or
            "no". Votes on each topic were examined, and strength of support determined
            based on these votes. Follow the "votes" link next to each topic for details.
    </dd>

    <dt><a name="numbers"></a>Why should I read in more depth than just the numbers?</dt>

    <dd>
        <p>A few people have asked why we publish statistics on how often Representatives use
            alliterative phrases, such as "she sells seashells".
        </p>

        <p>Simply put, we realise that data such as the number of debates spoken in Parliament
            means little in terms of a Representative's actual performance. Representatives do lots of useful
            things which we don't count yet, and some which we never could. Even when we
            do, a count doesn't measure the quality of a Representative's contribution.</p>

        <p>On the UK site <a href="https://www.theyworkforyou.com">TheyWorkForYou</a>, they used to publish absolute
            rankings of, for
            instance, the most verbose MPs. Then, they were hearing from real MP's researchers who
            admitted to tabling questions to increase their boss' rankings. So, TheyWorkForYou
            became concerned about the use of these statistics</p>

        <p> We've followed their lead by doing two things. We have silly statistics, to catch your attention.
            And we don't have absolute rankings. Instead of saying a Representative is exactly
            5th for giving out verbiage in the chamber, we just say that they are
            "well above average".

        <p>Our advice &mdash; when you're judging your Representative, read some of their speeches,
            check out their website, even go to a local meeting and ask them a question.
            Use OpenAustralia as a gateway, rather than a simple place to find a number
            measuring competence.

        <p>If you have other suggestions for useful metrics, send them to the <a
                href="mailto:contact&#64;openaustralia.org">usual
                address</a>. We have a few ideas ourselves to keep you on your toes.

    </dd>

    <!-- end old faq entry -->

    <!-- start new faq entry -->
    <dt><a name="api"></a>Do you have the data as a spreadsheet file, XML or in an API?</dt>
    <dd>
        <p>Yes. If you just need a spreadsheet of
            representatives, you'll find one on the right hand side of <a href="/mps">this page</a>. If you need a
            full-blown API (Application Programming Interface), which gives you the power to do almost anything with our
            data, <a href="/api">we have that too</a>. We also give you <a href="https://data.openaustralia.org.au">direct
                access to the XML data</a> which gets loaded into our database.</p>

        <p>Please <a href="mailto:contact&#64;openaustralia.org">mail us</a> if you want help
            working out how to use the data, or want to hire us to make something specific
            for you.</p>
    </dd>
    <!-- end old faq entry -->

    <dt><a name="regmem"></a>What is the Register of Interests and how can I use it?</dt>
    <dd>
        <p>The Register of Interests contains information of financial interests, stocks and shares held, gifts received
            over a certain value, and memberships of Clubs and Associations for Senators and Representatives. These are
            the things that have been considered to be a potential influence on their behaviour in Parliament.</p>

        <p>Find out more at the <a
                href="https://www.aph.gov.au/Parliamentary_Business/Committees/Senate/Senators_Interests">Senate Standing
                Committee of Senators' Interests</a> and the <a
                href="https://www.aph.gov.au/Parliamentary_Business/Committees/House_of_Representatives_Committees?url=pmi/index.htm">Standing
                Committee of Privileges and Members' Interests</a>.</p>

        <p>You might also be interested in <a href="https://www.smh.com.au/politics">a searchable database</a>
            of the register, compiled by SMH. Note that this was created in 2012 and may not be up to
            date.</p>
    </dd>

    <dt><a name="regmem-links"></a>Why are there two Register of Interests links for some MPs?</dt>
    <dd>
        <p>In early 2009, OpenAustralia.org was <a
                href="https://www.oaf.org.au/2009/01/05/the-register-of-senators-interests-is-now-online-2/">the
                first to publish</a> the important Register of Interests online. We're happy to say that Australian
            Parliament House now publishes this register online themselves.</p>

        <p>That's also why you'll see two links on some MP pages. One links to the latest entry from parliament house
            and the other links to the last update that was sent to us. You should only ever need the latest copy from
            Parliament house, but you never know so we've kept our (older) copy available too.</p>
    </dd>

</dl>
</section>
