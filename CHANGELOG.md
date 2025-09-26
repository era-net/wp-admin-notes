## v1.1.1
- Fix dead help links in update process

## v1.1.0
- Improving performance by preloading note contents on hover

## v1.0.7
- Code cleanup
- handling codeblock overflow

## v1.0.6
- UI cleanup by implementing hover state for note actions

## v1.0.5
- Reposition new note admin bar button
- improve translations

## v1.0.4
- Fix issue [#9](https://github.com/era-net/wp-admin-notes/issues/9)
- Fixed interference bugs with other plugins
- Translated deletion confirm messages
- Improved ui controls

## v1.0.3
- Adding german translation
- New notes can now be cancelled
- Title is now editable when creating a new note

## v1.0.22
- Rewriting Plugin Headers and making them more readable
- Autoupdating is buggy we're implementing something simpler. While there are a few more steps for the user it is still easy to do.
    - Getting rid of the whole auto-update stuff and implementing update checks at the very end of `/wp-admin-notes.php`.
- "Skipping updates" implementation