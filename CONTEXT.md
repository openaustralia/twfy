# OpenAustralia.org.au

The PHP web application behind OpenAustralia.org.au, where people read Australian parliamentary debates and sign up to
email alerts about what their representatives say.

## Language

### Alert emails

**Suppression**:
A record that mail to an email address has failed permanently, so alert emails to that address are no longer sent. The
person's alerts are kept.
_Avoid_: Block, blacklist, bounce record

**Lift**:
Ending a suppression by hand, after the person has written in to say their mailbox works again. The suppression is kept
with the time it was lifted. Following a confirmation link never lifts one.
_Avoid_: Restore, unsuppress, resume, reactivate

**Paused**:
What the notice tells people when their address is suppressed. It is the plain-language word for people, not another
name for the record itself.
_Avoid_: Suspended, disabled, blocked
