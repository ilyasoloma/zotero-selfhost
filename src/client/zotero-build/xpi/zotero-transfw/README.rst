===================================
 A Translator Framework for Zotero
===================================

What?
-----

``framework.js`` is a framework for writing Zotero translators,
especially screen scrapers. It is intended to make it easier for new
and experienced developers to write Zotero translators by removing as
much boilerplate code as possible.

How?
----

To use ``framework.js``, paste the contents of the ``framework.js`` file
into your translator, between the ``{ ... }`` header and the code at the
bottom. On Unix, you can use the ``Makefile`` to translate ``.js.in``
files into ``.js`` files. See, e.g., ``SFGate.js.in``. You will need to
modify the ``FILES`` variable in the ``Makefile`` to include your target
``.js`` file.

More
----

See, e.g., the ``SFGate.js.in`` for an example. Between the
boilerplate, you will see a Javascript object that looks like::

  { itemType         : 'newspaperArticle',
    title            : FW.Xpath('//head/meta[@name="title"]/@content').text(),
    publicationTitle : "San Francisco Chronicle",
    date             : 
      FW.Xpath('//div[@class="articleheadings"]//p[@class="date"]').text(),
    creators         : 
      FW.Xpath('//div[@class="articleheadings"]//p[@class="byline author vcard"]').
        text().remove(/,.*$/).cleanAuthor("author"),
    attachments      : {
      url            : FW.Url(),
      title          : "SFGate Snapshot",
      type           : "text/html"
    }
  }

Each key, e.g., ``itemType`` or ``title``, is generally a Zotero
metadata field. Each value is either a string, in which case it never
changes, a filter, or a function.

Filters are the primary way that metadata is generated. Filters start
with a selector, which selects some part of the document. Often this
is ``FW.Xpath``, though we also see above ``FW.Url``. The filter then
contains a series of transformations which are performed on the list
of results, finally resulting in what is added to the metadata field.

For instance, the ``creators`` metadata field above first selects all
results from the document that match
``//div[@class="articleheadings"...`` as seen above. Following this,
each result has its textcontent generated with the ``.text()`` filter.
Then anything matching the regex ``/,.*$/`` is removed. Finally the
``Zotero.utilities.cleanAuthor`` method is called with the arguments
``(string, "author")``. The resulting strings (remember that there may
have been multiple matches to the xpath expression) are added to the
``creators`` field of the generated Zotero item.

Detect
------

If multiple FW.Scrapers are defined, choosing good ``detect`` criteria
is essential. The ``detect`` field is used to decide if a scraper
should or should be used.

If the detect criteria evaluates to a non-empty array, that scraper is
used for the page. If there is one scraper without a detect, it is
always used. If there are multiple scrapers whose detects evaluate to
non-empty or which have no detect, the behavior is undefined.

Contact
-------

For more information, please contact Erik Hetzner at
mailto:egh@e6h.org or the ``zotero-dev`` mailing list.

