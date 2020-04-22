<?php
declare(strict_types=1);

namespace App\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repositories\InvoiceRepository")
 * @ORM\Table(name="invoices")
 * @ORM\HasLifecycleCallbacks
 */
class Invoice
{

	use \Nette\SmartObject;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $no;

	/**
	 * @ORM\ManyToOne(targetEntity="User")
	 * @ORM\JoinColumn(name="user_id", nullable=false, referencedColumnName="id", onDelete="RESTRICT")
	 */
	private $user;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $type;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	private $paymentType;

	/**
	 * @ORM\Column(type="tinyint", options={"default" = 0}, length = 1)
	 */
	private $sent;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	private $invoicePdfUrl;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $emailIssuedInvoiceText;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $notificationEmail;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $emailPairedInvoiceText;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	private $customerEmail;

	/**
	 * @ORM\ManyToOne(targetEntity="Address")
	 * @ORM\JoinColumn(name="customer_address_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
	 */
	private $customerAddress;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	private $customerIdentificationNumber;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	private $customerVatId;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $customerSubject;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $customerStreet;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $customerLandRegistryNumber;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	private $customerHouseNumber;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $customerZip;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $customerCity;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $customerCountry;

	/**
	 * @ORM\ManyToOne(targetEntity="Address")
	 * @ORM\JoinColumn(name="supplier_address_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
	 */
	private $supplierAddress;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	private $supplierIdentificationNumber;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	private $supplierVatId;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $supplierSubject;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $supplierStreet;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $supplierLandRegistryNumber;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	private $supplierHouseNumber;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $supplierZip;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $supplierCity;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $supplierCountry;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 */
	private $supplierBankAccountNumber;

	/**
	 * @ORM\ManyToOne(targetEntity="BankAccount")
	 * @ORM\JoinColumn(name="bank_account_id", nullable=true, referencedColumnName="id", onDelete="CASCADE")
	 */
	private $bankAccount;

	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2, nullable=false)
	 */
	private $totalPrice;

	/**
	 * @ORM\OneToMany(targetEntity="InvoiceItem", mappedBy="invoice", cascade={"persist"})
	 */
	private $items;

	/**
	 * @ORM\ManyToMany(targetEntity="Payment", inversedBy="invoice", cascade={"persist"})
	 * @ORM\JoinTable(name="invoices_payments")
	 */
	private $payments;

	/**
	 * @ORM\Column(type="date", nullable=false)
	 */
	private $issuedDate;

	/**
	 * @ORM\Column(type="date", nullable=false)
	 */
	private $dueDate;

	/**
	 * @ORM\Column(type="date", nullable=true)
	 */
	private $paymentDate;

	/**
	 * @ORM\Column(type="string", nullable=false, options={"default" = 0, "length" = 10})
	 */
	private $variableSymbol;

	/**
	 * @ORM\ManyToOne(targetEntity="RegularInvoice", inversedBy="invoices")
	 * @ORM\JoinColumn(name="regular_invoice_id", nullable=true, referencedColumnName="id", onDelete="CASCADE")
	 */
	private $regularInvoice;

	public function __construct()
	{
		$this->items = new ArrayCollection();
		$this->payments = new ArrayCollection();
		$this->issuedDate = new \DateTime();
		$this->dueDate = new \DateTime();
		$this->paymentDate = new \DateTime();
	}

	public function getId()
	{
		return $this->id;
	}

	public function getNo()
	{
		return $this->no;
	}

	public function getUser()
	{
		return $this->user;
	}

	public function getType()
	{
		return $this->type;
	}

	public function getPaymentType()
	{
		return $this->paymentType;
	}

	public function getSent()
	{
		return $this->sent;
	}

	public function getInvoicePdfUrl()
	{
		return $this->invoicePdfUrl;
	}

	public function getEmailIssuedInvoiceText()
	{
		return $this->emailIssuedInvoiceText;
	}

	public function getNotificationEmail()
	{
		return $this->notificationEmail;
	}

	public function getEmailPairedInvoiceText()
	{
		return $this->emailPairedInvoiceText;
	}

	public function getCustomerEmail()
	{
		return $this->customerEmail;
	}

	public function getCustomerAddress()
	{
		return $this->customerAddress;
	}

	public function getCustomerIdentificationNumber()
	{
		return $this->customerIdentificationNumber;
	}

	public function getCustomerVatId()
	{
		return $this->customerVatId;
	}

	public function getCustomerSubject()
	{
		return $this->customerSubject;
	}

	public function getCustomerStreet()
	{
		return $this->customerStreet;
	}

	public function getCustomerLandRegistryNumber()
	{
		return $this->customerLandRegistryNumber;
	}

	public function getCustomerHouseNumber()
	{
		return $this->customerHouseNumber;
	}

	public function getCustomerZip()
	{
		return $this->customerZip;
	}

	public function getCustomerCity()
	{
		return $this->customerCity;
	}

	public function getCustomerCountry()
	{
		return $this->customerCountry;
	}

	public function getSupplierAddress()
	{
		return $this->supplierAddress;
	}

	public function getSupplierIdentificationNumber()
	{
		return $this->supplierIdentificationNumber;
	}

	public function getSupplierVatId()
	{
		return $this->supplierVatId;
	}

	public function getSupplierSubject()
	{
		return $this->supplierSubject;
	}

	public function getSupplierStreet()
	{
		return $this->supplierStreet;
	}

	public function getSupplierLandRegistryNumber()
	{
		return $this->supplierLandRegistryNumber;
	}

	public function getSupplierHouseNumber()
	{
		return $this->supplierHouseNumber;
	}

	public function getSupplierZip()
	{
		return $this->supplierZip;
	}

	public function getSupplierCity()
	{
		return $this->supplierCity;
	}

	public function getSupplierCountry()
	{
		return $this->supplierCountry;
	}

	public function getSupplierBankAccountNumber()
	{
		return $this->supplierBankAccountNumber;
	}

	public function getBankAccount()
	{
		if ($this->bankAccount) {
			return $this->bankAccount;
		}
	}

	public function getTotalPrice()
	{
		return (float) $this->totalPrice;
	}

	public function getItems()
	{
		return $this->items;
	}

	public function getPayments()
	{
		return $this->payments;
	}

	public function getIssuedDate()
	{
		return $this->issuedDate;
	}

	public function getDueDate()
	{
		return $this->dueDate;
	}

	public function getPaymentDate()
	{
		return $this->paymentDate;
	}

	public function getVariableSymbol()
	{
		return $this->variableSymbol;
	}

	public function getRegularInvoice()
	{
		return $this->regularInvoice;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function setNo($no)
	{
		$this->no = $no;
	}

	public function setUser(User $user)
	{
		$this->user = $user;
	}

	public function setType($type)
	{
		$this->type = $type;
	}

	public function setPaymentType($paymentType)
	{
		$this->paymentType = $paymentType;
	}

	public function setSent($sent)
	{
		$this->sent = $sent;
	}

	public function setInvoicePdfUrl($invoicePdfUrl)
	{
		$this->invoicePdfUrl = $invoicePdfUrl;
	}

	public function setEmailIssuedInvoiceText($emailIssuedInvoiceText)
	{
		$this->emailIssuedInvoiceText = $emailIssuedInvoiceText;
	}

	public function setNotificationEmail($notificationEmail)
	{
		$this->notificationEmail = $notificationEmail;
	}

	public function setEmailPairedInvoiceText($emailPairedInvoiceText)
	{
		$this->emailPairedInvoiceText = $emailPairedInvoiceText;
	}

	public function setCustomerEmail($customerEmail)
	{
		$this->customerEmail = $customerEmail;
	}

	public function setCustomerAddress($customerAddress)
	{
		$this->customerAddress = $customerAddress;
	}

	public function setCustomerIdentificationNumber($customerIdentificationNumber)
	{
		$this->customerIdentificationNumber = $customerIdentificationNumber;
	}

	public function setCustomerVatId($customerVatId)
	{
		$this->customerVatId = $customerVatId;
	}

	public function setCustomerSubject($customerSubject)
	{
		$this->customerSubject = $customerSubject;
	}

	public function setCustomerStreet($customerStreet)
	{
		$this->customerStreet = $customerStreet;
	}

	public function setCustomerLandRegistryNumber($customerLandRegistryNumber)
	{
		$this->customerLandRegistryNumber = $customerLandRegistryNumber;
	}

	public function setCustomerHouseNumber($number)
	{
		$this->customerHouseNumber = $number == "" ? NULL : $number;
	}

	public function setCustomerZip($customerZip)
	{
		$this->customerZip = $customerZip;
	}

	public function setCustomerCity($customerCity)
	{
		$this->customerCity = $customerCity;
	}

	public function setCustomerCountry($customerCountry)
	{
		$this->customerCountry = $customerCountry;
	}

	public function setSupplierAddress($supplierAddress)
	{
		$this->supplierAddress = $supplierAddress;
	}

	public function setSupplierIdentificationNumber($supplierIdentificationNumber)
	{
		$this->supplierIdentificationNumber = $supplierIdentificationNumber;
	}

	public function setSupplierVatId($supplierVatId)
	{
		$this->supplierVatId = $supplierVatId;
	}

	public function setSupplierSubject($supplierSubject)
	{
		$this->supplierSubject = $supplierSubject;
	}

	public function setSupplierStreet($supplierStreet)
	{
		$this->supplierStreet = $supplierStreet;
	}

	public function setSupplierLandRegistryNumber($supplierLandRegistryNumber)
	{
		$this->supplierLandRegistryNumber = $supplierLandRegistryNumber;
	}

	public function setSupplierHouseNumber($number)
	{
		$this->supplierHouseNumber = $number == "" ? NULL : $number;
	}

	public function setSupplierZip($supplierZip)
	{
		$this->supplierZip = $supplierZip;
	}

	public function setSupplierCity($supplierCity)
	{
		$this->supplierCity = $supplierCity;
	}

	public function setSupplierCountry($supplierCountry)
	{
		$this->supplierCountry = $supplierCountry;
	}

	public function setSupplierBankAccountNumber($supplierBankAccountNumber)
	{
		$this->supplierBankAccountNumber = $supplierBankAccountNumber;
	}

	public function setBankAccount($bankAccount)
	{
		$this->bankAccount = $bankAccount;
	}

	public function setTotalPrice($totalPrice)
	{
		$this->totalPrice = $totalPrice;
	}

	public function setPayments($payments)
	{
		$this->payments->clear();
		foreach ($payments as $payment) {
			$this->payments->add($payment);
		}
	}

	public function addPayment(Payment $payment)
	{
		if (!$this->payments->contains($payment)) {
			$this->payments->add($payment);
		}
	}

	public function setItems($items)
	{
		$this->items->clear();
		foreach ($items as $item) {
			$this->items->add($item);
		}
	}

	public function addItem(InvoiceItem $item)
	{
		if (!$this->items->contains($item)) {
			$this->items->add($item);
		}
	}

	public function setIssuedDate($issuedDate)
	{
		$this->issuedDate = $issuedDate;
	}

	public function setDueDate($dueDate)
	{
		$this->dueDate = $dueDate;
	}

	public function setPaymentDate($date)
	{
		$this->paymentDate = $date == "" ? NULL : $date;
	}

	public function setVariableSymbol($variableSymbol)
	{
		$this->variableSymbol = $variableSymbol;
	}

	public function setRegularInvoice($regularInvoice)
	{
		$this->regularInvoice = $regularInvoice;
	}
}
