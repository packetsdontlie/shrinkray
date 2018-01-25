# shrinkray
A PHP script to progressively shrink the content of a text file to meet the goals of a size limit

Shrinkray receives a large text document and takes steps to make the text smaller by progressively removing information from the document.  This script is a one page web service.  It requires no additional code.

## Actions for Shrinkray

* ax=lim - reports on the current limit being enforced by Shrinkray
* ax=sr - the main shrink ray method

## Variables for Shrinkray

srpath = the path to a file you want to GET with shrinkray (the web server needs the RIGHT permissions to this path)

## Typical Usage Patterns

<pre>
curl --silent http://host/shrinkray/v1/?ax=lim
</pre>

This will return the limit being enforced by Shrinkray.

<pre>
curl --silent -v http://host/shrinkray/v1/?ax=sr&amp;srpath=/path/to/file
</pre>

This will have Shrinkray fetch the document specified by 'srpath' and shrink it.

## Returns

Response code + payload. If the action is 'lim' the payload is the string representing the size limit. If the method is 'sr' and the status code is less than 399, the payload will be the shrunk document.

## Response Codes for Shrinkray

* HTTP/1.1 200 OK
* HTTP/1.1 230 Shrunk via tags
* HTTP/1.1 235 Shurnk via duplication
* HTTP/1.1 239 Shrunk via punctuation
* HTTP/1.1 240 Shrunk via number
* HTTP/1.1 245 Shrunk via lowercase
* HTTP/1.1 250 Shrunk via header
* HTTP/1.1 400 Bad Request
* HTTP/1.1 410 Gone
* HTTP/1.1 412 Precondition Failed
* HTTP/1.1 413 Request Entity Too Large
* HTTP/1.1 500 Internal Server Error

## Response Codes and Events

* 200 when you ask for the limit over GET or POST
* 230 when the HTML tags have been removed
* 235 when the duplicate strings have been removed
* 239 when punctuation (defined by unicode character class \p{Punctuation}) has been removed
* 240 when numbers (defined by the unicode character class \p{Number}) have been removed
* 245 when the words have been lower cased and the duplicates have been removed
* NOTE: 230-245 are incremental, they include the previous steps
* 250 when the text could not be shrunk and a chunk of the document lower than ax=lim has been returned to you in the payload
* 400 when you use the ax=sr and do not provide srpath (GET)
* 410 when you specify the srpath and the file does not exist
* 412 when you provide a document that is smaller than ax=lim and no action is taken
* 413 when all the shrinking processes have failed and nothing is done to your document
* 500 when something unexpected happens

## Using the Response Code

If your response is within 230 to 250, your document has been shrunk and the smaller document is in the payload. It is up to you to persist this document.
