<?php
class Travel
{
	public $createdAt;
	public $employeeName;
	public $departure;
	public $destination;
	public $price;
	public $companyId;
}
class Company
{
	public $id;
	public $createdAt;
	public $name;
	public $parentId;
    public $cost;
    public $children;

    public function __construct($row) {
        $this->id = $row->id;
        $this->createdAt = $row->createdAt;
        $this->name = $row->name;
        $this->parentId = $row->parentId;

    }
}
class TestScript
{
    public $companyList;
    public $travelList;

    public function fetchJsonData($url)
    {
        $channel = curl_init($url);
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($channel, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($channel);
        curl_close($channel);
        return json_decode($response);
    }

    public function fetchCompanyData()
    {
        $url = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies';
        return $this->fetchJsonData($url);
    }
    
    public function fetchTravelData()
    {
        $url = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels';
        return $this->fetchJsonData($url);
    }

    public function getCompanyTravelCost($companyId)
    {
        $cost = 0;
        foreach ($this->travelList as $travel) {
            if ($travel->companyId == $companyId) {
                $cost += $travel->price;
            }
        }
        $children = array_filter($this->companyList, function($company) use ($companyId) {
            return $company->parentId == $companyId;
        });

        foreach ($children as $child) {
            $cost += $this->getCompanyTravelCost($child->id);
        }
        return $cost;
    }

    public function getFinalData(){

        $this->companyList = $this->fetchCompanyData();
        $this->travelList = $this->fetchTravelData();
        foreach ($this->companyList as &$company) {
            $company = new Company($company);
        }
        $parentCompanies = array_filter($this->companyList, function($company) {
            return empty($company->parentId);
        });
        foreach ($parentCompanies as &$company) {
            $company->cost = $this->getCompanyTravelCost($company->id);
            $company->children = $this->getCompanyChildren($company->id);
        }
        return $parentCompanies;
    }


    public function getCompanyChildren($companyId)
    {
        $children = array_filter($this->companyList, function($company) use ($companyId) {
            return $company->parentId == $companyId;
        });
        foreach ($children as &$company) {
            $company->cost = $this->getCompanyTravelCost($company->id);
            $company->children = $this->getCompanyChildren($company->id);
        }
        return $children;
    }

    public function execute()
    {
        $start = microtime(true);
        
        echo json_encode($this->getFinalData());

        echo 'Total time: '.  (microtime(true) - $start);
    }
}
(new TestScript())->execute();