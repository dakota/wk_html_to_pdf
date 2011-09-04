Currently only for CakePHP 1.3
 
Put into plugins/wk\_html\_to_pdf
 
Two simple ways to use:
 
Method 1:

    //Route.php
    Router::parseExtensions('pdf');

    //Controller or AppController   
    $components = array('RequestHandler', 'WkHtmlToPdf.WkHtmlToPdf');

Method 2:

    //In your action
    $this->view = 'WkHtmlToPdf.WkHtmlToPdf';
 

Installing WkHtmlToPdf:

* Goto http://code.google.com/p/wkhtmltopdf/
* Download the version for your server
* Put the binary in /usr/bin (by default), call it _wkhtmltopdf_
