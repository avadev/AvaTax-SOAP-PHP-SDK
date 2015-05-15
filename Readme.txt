PHP SDK V 5.5.0.0 

To install the SDK, simply copy the AvaTaxPHP directory to a convenient location 
preserving the directory structure in the archive.  Note that SSL  and SoapClient 
support must be enabled for your PHP interpretor:

For windows, this means adding:
extension=php_soap.dll
exetnsion=php_openssl.dll
 
For *nix, it will probably be necessary to recompile your PHP interpretor with
these things enabled.

Finally, this SDK is written using the native SoapClient support first built
into PHP V5.0, and not stable until V5.2 .Therefore, it is a minimum 
requirement to use PHP V5.2 or greater.  The latest version can be downloaded at no
charge from www.php.net.

Change History
---------------
5.5.0.0- Change in version number to be in sync with service


5.4.0.2 - 03rd August 2009
Changes required to resolve QB Issue 1150  



5.4.0.1 - 26th May 2009
AvaTax.php- Issue 10523 - the use of ‘\’ in file paths is causing the autoload function to fail on *nix.
			Modifying to use ‘/’ instead seems to work fine on both *nix and Windows.

5.4.0.0 - 05th May 2009
ATConfig.php - Updated adpater version to 5.4.0.0



The Avalara SDK Team
January, 2009
