<?php
declare(strict_types=1);

namespace AppTests\Presenters;

use App\Entities\Payment;
use App\Entities\Invoice;
use App\Entities\InvoiceItem;
use App\EntityManagerDecorator;
use App\Repositories\InvoiceRepository;
use Mangoweb\Tester\Infrastructure\TestCase;
use Nette\DI\Container;
use Nette\Security\User;
use Tester\Assert;
use Tests\App\Helpers;

$testContainerFactory = require __DIR__ . '/../bootstrap.php';

class InvoicesPresenterTest extends TestCase
{

	use TPresenter;

	public function testActions(User $user)
	{
		$identity = $this->getLoggedInIdentity($user, 1, ['admin']);
		$this->checkAction($identity, 'Invoices:Default');
		$this->checkAction($identity, 'Invoices:Default', ['action' => 'add', 'type' => 'issued']);
		$this->checkAction($identity, 'Invoices:Default', ['action' => 'add', 'type' => 'accepted']);
		$this->checkAction($identity, 'Invoices:Default', ['action' => 'addPayment', 'invoiceId' => 1]);
		$this->checkAction($identity, 'Invoices:Default', ['action' => 'showDetail', 'id' => 1, 'type' => 'issued']);
	}

	public function testAddIssuedInvoiceForm(EntityManagerDecorator $em, User $user)
	{
		$params = [
			"no" => "123/56",
			"type" => "issued",
			"paymentType" => "bank_transfer",
			"cashBookDescription" => "test issued invoice payment",
			"emailIssuedInvoiceText" => "<p>Test message</p>",
			"notificationEmail" => "test@example.com",
			"emailPairedInvoiceText" => "<p>Test</p>",
			"subjectAddressId" => 3,
			"bankAccountId" => 1,
			"issuedDate" => strftime("%Y-%m-%d"),
			"dueDate" => date('Y-m-d', strtotime("+2 weeks")),
			"paymentDate" => date('Y-m-d', strtotime("+1 week")),
			"variableSymbol" => 123,
		];


		$items = [
			0 => ["title" => "test",
				"quantity" => 1,
				"unit" => "ks",
				"unitPrice" => "10"
			],
			1 => ["title" => "testb",
				"quantity" => 10,
				"unit" => "ks",
				"unitPrice" => "200",
			],
		];

		$payments = [
			0 => ['description' => 'Test',
				'amount' => '2010.00',
				'taxType' => 1
		]];

		$invoice = $params;
		$invoice['items'] = $items;
		$invoice['payments'] = $payments;

		$testRequest = $this->presenterTester->createRequest('Invoices:Default')
			->withIdentity($this->getLoggedInIdentity($user, 1, ['admin']))
			->withParameters(['action' => 'add', 'type' => 'issued'])
			->withForm('editInvoiceForm-editInvoiceForm', $invoice);

		$testResponse = $this->presenterTester->execute($testRequest);
		$testResponse->assertFormValid('editInvoiceForm-editInvoiceForm');
		$testResponse->assertRedirectsUrl('#invoices/show-detail/3\?type=issued&_fid#i');

		unset($params['subjectAddressId']);
		unset($params['bankAccountId']);
		unset($params['cashBookDescription']);

		$invoiceRepository = $em->getRepository(Invoice::class);

		$helpers = new Helpers();
		$resultInvoices = $helpers->getArrayResult($invoiceRepository, $params);

		foreach ($resultInvoices as $resultInvoice) {
			$resultInvoice['issuedDate'] = $resultInvoice['issuedDate']->format('Y-m-d');
			$resultInvoice['dueDate'] = $resultInvoice['dueDate']->format('Y-m-d');
			$resultInvoice['paymentDate'] = $resultInvoice['paymentDate']->format('Y-m-d');
			$resultInvoice['variableSymbol'] = (int) $resultInvoice['variableSymbol'];

			foreach ($params as $param) {
				Assert::contains($param, $resultInvoice);
			}
		}

		$invoiceItemRepository = $em->getRepository(InvoiceItem::class);

		$resultItems = $helpers->getArrayResult($invoiceItemRepository, ['invoice' => 1]);

		foreach ($resultItems as $resultItem) {
			foreach ($resultItem as $value) {
				Assert::contains($value, $resultItem);
			}
		}

		$paymentRepository = $em->getRepository(Payment::class);

		$resultPayments = $helpers->getArrayResult($paymentRepository, ['documentId' => 4, 'documentType' => 'invoice']);

		foreach ($resultPayments as $resultPayment) {
			foreach ($resultPayment as $value) {
				Assert::contains($value, $resultPayment);
			}
		}
	}

	public function testEditIssuedInvoiceForm(EntityManagerDecorator $em, User $user)
	{
		$post = [
			"no" => "123",
			"type" => "issued",
			"paymentType" => "bank_transfer",
			"cashBookDescription" => "test b issued invoice payment",
			"invoiceId" => "1",
			"emailIssuedInvoiceText" => "<p>Test message</p>",
			"notificationEmail" => "test@example.com",
			"emailPairedInvoiceText" => "<p>Test</p>",
			"subjectAddressId" => 3,
			"bankAccountId" => 1,
			"issuedDate" => strftime("%Y-%m-%d"),
			"dueDate" => date('Y-m-d', strtotime("+2 weeks")),
			"paymentDate" => date('Y-m-d', strtotime("+1 week")),
			"variableSymbol" => 123,
		];

		$testRequest = $this->presenterTester->createRequest('Invoices:Default')
			->withIdentity($this->getLoggedInIdentity($user, 1, ['admin']))
			->withParameters(['action' => 'edit', 'id' => 1, 'type' => 'issued'])
			->withForm('editInvoiceForm-editInvoiceForm', $post);

		$testResponse = $this->presenterTester->execute($testRequest);
		$testResponse->assertFormValid('editInvoiceForm-editInvoiceForm');
		$testResponse->assertRedirectsUrl('#invoices/show-detail/1\?type=issued&_fid#i');

		$params = $post;

		unset($params['subjectAddressId']);
		unset($params['bankAccountId']);
		unset($params['cashBookDescription']);

		$params['id'] = $params['invoiceId'];
		unset($params['invoiceId']);

		$invoiceRepository = $em->getRepository(Invoice::class);

		$helpers = new Helpers();

		$resultInvoices = $helpers->getArrayResult($invoiceRepository, []);
		$resultInvoice = $resultInvoices[0];
		$resultInvoice['issuedDate'] = $resultInvoice['issuedDate']->format('Y-m-d');
		$resultInvoice['dueDate'] = $resultInvoice['dueDate']->format('Y-m-d');
		$resultInvoice['paymentDate'] = $resultInvoice['paymentDate']->format('Y-m-d');
		$resultInvoice['variableSymbol'] = (int) $resultInvoice['variableSymbol'];

		foreach ($params as $param) {
			\Tester\Assert::contains($param, $resultInvoice);
		}
	}

	public function testDelete(Container $container, EntityManagerDecorator $em, User $user)
	{
		$invoiceId = 2;
		$invoiceItemId = 3;
		$paymentId = 3;

		$helpers = new Helpers();

		/** @var InvoiceRepository $invoiceRepository */
		$invoiceRepository = $em->getRepository(Invoice::class);
		/** @var Invoice $invoice */
		$invoice = $invoiceRepository->find($invoiceId);

		$testRequest = $this->presenterTester->createRequest('Invoices:Default')
			->withIdentity($this->getLoggedInIdentity($user, 1, ['admin']))
			->withParameters(['action' => 'edit', 'id' => $invoiceId])
			->withAjax(true)
			->withSignal('editInvoiceForm-invoiceDetailGrid-deleteInvoiceItem', ['invoiceItemId' => $invoiceItemId]);

		$testResponse = $this->presenterTester->execute($testRequest);
		$testResponse->assertJson();

		$invoiceItemRepository = $em->getRepository(InvoiceItem::class);
		$itemsCount = $helpers->countResult($invoiceItemRepository, ['id' => $invoiceItemId]);
		Assert::equal("0", $itemsCount);

		$testRequest = $this->presenterTester->createRequest('Invoices:Default')
			->withIdentity($this->getLoggedInIdentity($user, 1, ['admin']))
			->withParameters(['action' => 'edit', 'id' => $invoiceId])
			->withAjax(true)
			->withSignal('editInvoiceForm-pairedPaymentsGrid-delete', ['paymentId' => $paymentId]);

		$testResponse = $this->presenterTester->execute($testRequest);
		$testResponse->assertJson();

		$paymentRepository = $em->getRepository(Payment::class);
		$paymentsCount = $helpers->countResult($paymentRepository, ['id' => $paymentId]);
		Assert::equal("0", $paymentsCount);

		$testRequest = $this->presenterTester->createRequest('Invoices:Default')
			->withIdentity($this->getLoggedInIdentity($user, 1, ['admin']))
			->withParameters(['action' => 'deleteInvoice', 'id' => $invoiceId]);

		$testResponse = $this->presenterTester->execute($testRequest);
		$testResponse->assertRedirectsUrl('#invoices\?_fid#i');

		$absoluteUploadedDir = $container->parameters['absoluteUploadedDir'];

		Assert::false(file_exists($absoluteUploadedDir . '/' . $invoice->getInvoicePdfUrl()));
	}
}

InvoicesPresenterTest::run($testContainerFactory);
