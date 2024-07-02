## v1.0.4
- Fix issue [#9](https://github.com/era-net/wp-admin-notes/issues/9)
- Fix interference bugs with other plugins 

## v1.0.3
- Adding german translation
- New notes can now be cancelled
- Title is now editable when creating a new note

## v1.0.22
- Rewriting Plugin Headers and making them more readable
- Autoupdating is buggy we're implementing something simpler. While there are a few more steps for the user it is still easy to do.
    - Getting rid of the whole auto-update stuff and implementing update checks at the very end of `/wp-admin-notes.php`.
- "Skipping updates" implementation