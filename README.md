# Link submissions for Moodle assignments (assignsubmission_link)

Students hand in links instead of files: a GitHub repository, a Google Doc, a Figma file, a Canva design, a video, a live website. Teachers choose how many links and, if they want, which sites the links must go to.

## Use it

1. Install it like any plugin. It appears under Site administration > Plugins > Activity modules > Assignment > Submission plugins.
2. In an assignment's settings, under **Submission types**, tick **Links**.
3. Set **Links per submission** (1 to 10) and, optionally, **Allowed sites**, for example `github.com, docs.google.com`. A site also allows its subdomains, so `github.com` allows `gist.github.com`. Leave it empty to accept any web link.

Students see one field per link. Teachers see the links in the submissions table and the grader, and "Download all submissions" includes a `links.html` page for each student.

## Good to know

- Only full `https://` or `http://` links are accepted. Links with a user name in them (`https://github.com@other.site/`) are refused, so a link cannot pretend to go to an allowed site.
- If one link is not allowed, nothing is saved, and the student sees which sites are allowed.
- Links open in a new tab, without passing the Moodle page to the other site.
- Works with the Moodle app and web services (`mod_assign_save_submission` with `plugindata[assignsubmission_link][]`), backup and restore, new attempts based on the previous one, and privacy export and deletion.
- Moodle stores the link, not the page behind it: if the student changes the document later, the link shows the new version. Ask for a link to a fixed version (a commit, a release, a "view only" copy) when that matters.

Moodle 4.5 to 5.3. Built by DevLMS: https://devlms.com
