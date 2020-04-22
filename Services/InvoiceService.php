<?php
declare(strict_types=1);

namespace App\Services;

use App\EntityManagerDecorator;
use App\Entities\Invoice;
use App\Entities\InvoiceItem;
use App\Entities\Payment;
use App\Entities\User;
use App\Repositories\InvoiceRepository;
use App\Repositories\InvoiceItemRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\UserRepository;
use Nette;

class InvoiceService
{

	/**
	 * @var string
	 */
	public $uploadDir;

	/**
	 *
	 * @var EntityManagerDecorator
	 */
	private $em;

	/**
	 * @var InvoiceItemRepository
	 */
	private $invoiceItemRepository;

	/**
	 * @var InvoiceRepository
	 */
	private $invoiceRepository;

	/**
	 * @var PaymentRepository
	 */
	private $paymentRepository;

	/**
	 * @var UserRepository
	 */
	private $userRepository;

	public function __construct(
		$uploadDir,
		EntityManagerDecorator $em
	)
	{
		$this->uploadDir = $uploadDir;
		$this->em = $em;
		$this->invoiceItemRepository = $em->getRepository(InvoiceItem::class);
		$this->invoiceRepository = $em->getRepository(Invoice::class);
		$this->paymentRepository = $em->getRepository(Payment::class);
		$this->userRepository = $em->getRepository(User::class);
	}

	public function saveInvoice($userId, $values)
	{
		if ($values['invoiceId']) {
			return $this->updateInvoice($userId, $values);
		} else {
			return $this->insertInvoice($userId, $values);
		}
	}

	private function updateInvoice($userId, $values)
	{
		$this->em->getConnection()->beginTransaction();

		$user = $this->userRepository->find($userId);

		$invoice = $this->invoiceRepository->findOneBy(['id' => $values->invoiceId, 'user' => $userId]);

		if (!$invoice) {
			throw new Nette\Application\BadRequestException("Such invoice doesn't exist!");
		}

		if (isset($values->no)) {
			$invoice->setNo($values->no);
		}

		if (isset($values->type)) {
			$invoice->setType($values->type);
		}

		if (isset($values->paymentType)) {
			$invoice->setPaymentType($values->paymentType);
		}

		if (isset($values->sent)) {
			$invoice->setSent($values->sent);
		}

		if (isset($values->issuedDate)) {
			$invoice->setIssuedDate($values->issuedDate);
		}

		if (isset($values->dueDate)) {
			$invoice->setDueDate($values->dueDate);
		}

		$invoice->setPaymentDate($values->paymentDate);

		if ($values->type == 'issued' && $invoice->getInvoicePdfUrl() && $values->sent == 0) {
			$invoicePdfFile = $this->uploadDir . '/' . $userId . '/' . $invoice->getInvoicePdfUrl();

			if (file_exists($invoicePdfFile) && is_file($invoicePdfFile)) {
				unlink($invoicePdfFile);
			}
		}

		if (isset($values->invoicePdfUrl)) {
			$invoice->setInvoicePdfUrl($values->invoicePdfUrl);
		} else {
			$invoice->setInvoicePdfUrl(null);
		}

		if ($values->type == "issued") {
			if (isset($values->emailIssuedInvoiceText) && $values->emailIssuedInvoiceText != $invoice->getEmailIssuedInvoiceText()) {
				$invoice->setEmailIssuedInvoiceText($values->emailIssuedInvoiceText);
			}
		}

		if (isset($values->emailPairedInvoiceText) && $values->emailPairedInvoiceText != $invoice->getEmailPairedInvoiceText()) {
			$invoice->setEmailPairedInvoiceText($values->emailPairedInvoiceText ? $values->emailPairedInvoiceText : NULL);
		}

		$invoice->setNotificationEmail($values->notificationEmail ? $values->notificationEmail : NULL);

		if (isset($values->variableSymbol)) {
			$invoice->setVariableSymbol($values->variableSymbol);
		} else {
			if (isset($values->no)) {
				$invoice->setVariableSymbol($values->no);
			}
		}

		try {
			$this->em->flush();

			$this->em->getConnection()->commit();
		} catch (\Exception $e) {
			bdump($e);

			$this->em->getConnection()->rollBack();
		}

		return $invoice;
	}

	private function insertInvoice($userId, $values)
	{
		$invoice = new Invoice();

		$user = $this->userRepository->find($userId);
		
		$invoice->setUser($user);

		$invoice->setNo($values->no);
		$invoice->setType($values->type);
		$invoice->setPaymentType($values->paymentType);
		$invoice->setSent(0);

		$invoice->setIssuedDate($values->issuedDate);
		$invoice->setDueDate($values->dueDate);
		$invoice->setPaymentDate($values->paymentDate);

		if ($values->invoicePdfUrl) {
			$invoice->setInvoicePdfUrl($values->invoicePdfUrl);
		}

		if ($values->type == "issued") {
			if (isset($values->emailIssuedInvoiceText) && $values->emailIssuedInvoiceText != $invoice->getEmailIssuedInvoiceText()) {
				$invoice->setEmailIssuedInvoiceText($values->emailIssuedInvoiceText);
			}
		}

		if (isset($values->emailPairedInvoiceText) && $values->emailPairedInvoiceText != $invoice->getEmailPairedInvoiceText()) {
			$invoice->setEmailPairedInvoiceText($values->emailPairedInvoiceText ? $values->emailPairedInvoiceText : NULL);
		}

		$invoice->setNotificationEmail($values->notificationEmail ? $values->notificationEmail : NULL);

		$invoice->setVariableSymbol($values->variableSymbol);

		$invoice->setTotalPrice(0);

		$invoiceItems = null;
		foreach ($values->items as $id => $item) {
			$invoiceItems[$id] = new InvoiceItem();

			$invoiceItems[$id]->setInvoice($invoice);
			$invoiceItems[$id]->setTitle($item->title);
			$invoiceItems[$id]->setQuantity($item->quantity);
			$invoiceItems[$id]->setUnit($item->unit);
			$invoiceItems[$id]->setUnitPrice($item->unitPrice);

			$invoice->addItem($invoiceItems[$id]);
		}

		$this->em->persist($invoice);
		$this->em->flush();

		$payments = null;
		foreach ($values->payments as $id => $payment) {
			$payments[$id] = new Payment();

			$payments[$id]->setUser($user);

			$payments[$id]->setDescription($payment->description);
			$payments[$id]->setDocumentType("invoice");
			$payments[$id]->setPaymentType($values->paymentType);
			$payments[$id]->setPaymentDate($values->paymentDate);
			$payments[$id]->setDocumentId($invoice->getId());
			$payments[$id]->setDocumentNo($invoice->getNo());
			$payments[$id]->setAmount($payment->amount);
			$payments[$id]->setType($paymentType);
			$payments[$id]->setTaxType($payment->taxType);

			$invoice->addPayment($payments[$id]);
			$this->em->persist($payments[$id]);
		}

		$this->em->flush();

		return $invoice;
	}

	public function deleteInvoice($userId, $id)
	{
		$invoice = $this->invoiceRepository->findOneBy(["id" => $id, "user" => $userId]);

		if (!$invoice) {
			throw new Nette\Application\BadRequestException("Invoice doesn't exist");
		}

		if ($invoice->getInvoicePdfUrl()) {
			$invoicePdfFile = $this->uploadDir . '/' . $invoice->getInvoicePdfUrl();

			if (file_exists($invoicePdfFile) && is_file($invoicePdfFile)) {
				unlink($invoicePdfFile);
			}
		}

		$this->em->remove($invoice);
		$this->em->flush();
		$this->em->clear();
	}

	public function deleteInvoiceItem($userId, $invoiceId, $invoiceItemId)
	{
		$invoice = $this->invoiceRepository->findOneBy(["id" => $invoiceId, "user" => $userId]);

		if ($invoice) {
			$invoiceItem = $this->invoiceItemRepository->findOneBy(["id" => $invoiceItemId, "invoice" => $invoice]);

			if ($invoiceItem) {
				$this->em->remove($invoiceItem);
				$this->em->flush();
			} else {
				throw new Nette\Application\BadRequestException("Invoice item ID: $invoiceItemId with userID: $userId doesn't exist!");
			}
		} else {
			throw new Nette\Application\BadRequestException("Invoice with ID: $invoiceId and userID: $userId doesn't exist!");
		}
	}
}
