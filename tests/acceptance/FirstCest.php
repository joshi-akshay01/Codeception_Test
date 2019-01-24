<?php 

use \Codeception\Util\Locator;

class FirstCest  
{

    public function _before(AcceptanceTester $I)
    {	
  

    }

    public function _after(AcceptanceTester $I)
    {

    }

   public function Check_Authentication_Atlantic_Broadband(AcceptanceTester $I)
	{
		global $Client,$Username,$Password;
		$ini_array = (parse_ini_file("data.ini",true, INI_SCANNER_RAW));
		foreach ($ini_array as $data) {
			$I->amOnPage('/');
			$I->click('html body div#body-container form input');
			$I->selectOption('//*[@id="dropdownlist"]','Atlantic Broadband (auth.atlanticbb.net)');
			$I->click('.btn');
			$I->fillfield('//*[@id="username"]','syn11@atlanticbb.net');
			$I->fillfield('//*[@id="password"]','syn@cortest');
			$I->click('//*[@id="login"]'); 
			$I->wait(10);
			$I->seeInTitle('SAML 2.0 SP Demo Example');
			$I->click('/html/body/div[2]/p[4]/a');
		}
	}		

}

  


