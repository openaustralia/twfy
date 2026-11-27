# Help pages: content to change in a separate PR

Written while converting the help pages to Blade (branch `feature/help-pages`). The conversion keeps every word as it
was, typos included. This list is what we'd want to change afterwards.

How sure each note is:

- **Verified** - checked against the code, the design system or the live page.
- **Unverified** - looks wrong, but needs checking against production, a person, or a source I couldn't reach.

Voice and wording rules come from the OAF Design System README and `source/openaustralia.md`. They ask for plain
English, active voice, contractions, "people" not "users", no em dashes, sentence case headings, Australian English, and
"OpenAustralia.org.au" with the `.au` in prose.

## Needs a decision before anything else

| Page | Issue | Sure? |
|---|---|---|
| House rules | Written for comments ("Contributions", "don't re-submit your comment to more than one discussion"). Comments are being removed. | Verified: the comments PR is planned |
| House rules | "Please use your own name... the name you choose will be displayed on the site." People's names are no longer public since the account pages PR. | Verified |
| Privacy (and the help FAQ copy) | A thin legal-sounding promise ("We guarantee we will not sell or distribute any personal information"). Check it against what we really use: Postal for email, Plausible, Sentry. Changing it is a public position, so it needs CEO sign-off. | Unverified |
| How it works, Parsing status, Wikipedia changes | UK-only pages left alone in this PR. See "UK leftovers" below. | Verified |

## About us

- **Typos:** "accidently" and "oppurtunity". Verified.
- **Links:** `http://` (not `https://`) for theyworkforyou.com and rsp.com.au. Verified. Check they still resolve.
- **Copyright wording:** "used with permission" for Hansard, and images "administered by AUSPIC" with a mailto link
  (auspic@aph.gov.au). Check both against the current APH copyright page. Unverified.
- **Name:** says "OpenAustralia" and "OpenAustralia.org". The style rule is "OpenAustralia.org.au". Verified.
- **Third-party images:** each volunteer has a Gravatar image, so every view of the page asks gravatar.com for 17
  images, with a hash of each volunteer's email in the address. This shares visitors' IP addresses with a third party and
  doesn't fit the privacy promise. Consider dropping the images. Verified.
- **UK volunteers:** a list of 21 TheyWorkForYou volunteers. Fine as a credit, but check the people are happy to be
  listed and the list is still wanted. Unverified.
- **Tone:** "in the belief that" is the "we believe" pattern the style guide says to cut, and the opening paragraphs
  are more editorial than the library voice ("a profound gift", "little wrong with Parliament that... won't fix").
  Verified against the guide.
- **Layout table:** volunteers are in a table with no header, only for layout. The conversion makes them a list.

## Help (the FAQ)

- **"Is this the whole of Hansard?"** says it goes back to "the beginning of 2006". The dev database starts on
  6 February 2007 and the design system says the collection has run since 2007. Run
  `SELECT MIN(hdate) FROM hansard;` on production. Also says divisions aren't included, but the site shows division
  results in the transcripts. Unverified.
- **"How is the voting record decided?"** describes topics and a "votes" link next to each. The member pages have
  neither now. The only voting link points to They Vote for You. This answer is out of date. Verified.
- **RSS answer:** recommends Sharpreader, NetNewsWire Lite, Bloglines and Google Reader. Bloglines closed in 2010 and
  Google Reader in 2013. Those links are dead or pointless. Unverified for the exact dates, but all four are long gone.
- **"Why should I read in more depth than just the numbers?"** says the UK TheyWorkForYou heard from MPs' researchers
  who admitted tabling questions to raise rankings. That is an unsourced claim about another organisation. Verified
  that the "silly statistics" it mentions still exist (`MemberStatistics::alliterations()`).
- **Privacy and cookie answers:** the privacy answer repeats the Privacy page word for word, so the two can drift.
  The cookie answer mentions logging in and the electoral division. Sign-in now also sets Laravel's session cookies.
  Check it still says what is true. Unverified.
- **Spreadsheet answer:** says the spreadsheet is "on the right hand side of this page" for `/mps`. CSV does exist
  (`?f=csv`), but check where the link sits now. It also links to `https://data.openaustralia.org.au` and offers to be
  hired "to make something specific for you". Check the link works and whether we want to offer paid work. Unverified.
- **Email address:** uses contact@openaustralia.org (no `.au`). Check the mailbox works and which domain we want.
  Unverified.
- **Tone and style:** "you lot", an em dash ("Our advice - when you're judging"), long sentences, "mere taster".
  Verified against the guide.
- **Markup:** the privacy answer never closes its `<dd>`, so the cookie question sits inside it. The conversion fixes
  this. Verified.
- **Register of interests:** the SMH database paragraph says the project is defunct and links to an archive copy.
  The 2009 "first to publish" claim links to an OAF blog post. Check the link still works. Unverified.

## Contact

- Says "OpenAustralia.org" without `.au`. Verified.
- "We seek responses to all these questions." reads oddly. Check what we actually promise. Unverified.
- Uses `CONTACTEMAIL` from the site config (contact@openaustralia.org). Same mailbox question as the help page.

## Get involved

- Two links are broken in the HTML (`<a>` where `</a>` should be). The conversion fixes the markup, not the words.
  Verified.
- Both code links go to `https://software.openaustralia.org/`. Check that still lands on the code. The repositories are
  on GitHub (`openaustralia/openaustralia`). Unverified.
- "I'd like to help out, but I'm not really a Techie." Check the tone. "Add a glossary entry" needs an account.

## House rules

See "Needs a decision". Also "Users of all ages and abilities" (the style rule says "people"), "get medieval" and
"No porn, no snuff, no filez etc." are informal. Verified against the guide.

## Privacy

See "Needs a decision". Whatever it says must match what the site does, including the new account pages.

## Link to us

- The box people paste into their site uses the old beige colours (`#EBECCF`, `#AE967F`), not the OpenAustralia
  navy and teal. Verified.
- The pasted code gives its inputs positive `tabindex` values (1 to 4), which makes keyboard order jump around on
  someone else's page. It also uses an `<h5>` as the box title. Both are accessibility problems in what we ask people
  to copy. Verified.
- "Google points mean prizes" is old search-engine talk. Verified.
- Check the two forms still work. They send `pc` to `/mp/` and `s` to `/search/`. Unverified.

## Search help

- The Firefox tip links to `mycroft.mozdev.org` and only shows in Firefox (it sniffs the browser). That site and the
  search plugins it served are long out of date. Probably remove it. Unverified.
- The example "Cole Inquiry" is Australian, so it's fine.

## UK leftovers (not converted in this PR)

These three are not Australian, and I'd remove them instead of redesigning them:

- **How it works** (`/how/`): a placeholder. It says "And as for the Lords..." and "The mysteries of parliament
  explained in simple text for all." Nothing links to it.
- **Parsing status** (`/parsing/`): reads `/home/fawkes/parldata/` and looks for UK directories (`wrans`, `wms`,
  `westminhall`, `lordspages`, `ni`). It cannot work here. Nothing links to it.
- **Wikipedia changes** (`/wikipedia/`): lists UK political organisations by IP address (Parliament, Conservative
  Central Office, Plaid Cymru, Labour, Liberal Democrats) and talks about edits to UK politicians' pages. Also uses
  beta@openaustralia.org. It is UK-only and names UK parties. Nothing links to it.

## Accessibility problems in the old pages (the conversion fixes these)

- No heading levels of their own, and headings jumping from `h3` to `h4`.
- A table used for layout (volunteers) and avatar images with alt text that repeats the adjacent name.
- Fragment anchors made with `<a name>` on a bare `<dt>`, and an unclosed `<dd>`.
- A live demo search box on the Link to us page with two forms and labelled fields that really submit, between the
  text and the code.
- Positive `tabindex` values in the copy-and-paste code (not fixed in the conversion, because it changes the code
  people paste).
