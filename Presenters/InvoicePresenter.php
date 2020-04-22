<?php

namespace App\Presenters;

use App\Components\IAcceptedInvoiceControlFactory;
use App\Components\IIssuedInvoiceControlFactory;
use App\Services\InvoicesService;
use Nette;
use Nette\Application\UI;
use Latte;
use Defr\QRPlatba\QRPlatba;
use Joseki\Application\Responses\PdfResponse;
use Ublaboo\DataGrid\DataGrid;

class InvoicePresenter extends BasePresenter
{
    /**
     * @var IAcceptedInvoiceControlFactory
     * @inject
    */
    public $acceptedInvoiceControlFactory;

    /**
     * @var IIssuedInvoiceControlFactory
     * @inject
    */
    public $issuedInvoiceControlFactory;

	/**
	 * @var InvoicesService
	*/
	public $invoicesService;

    /** @persistent */
    public $pagination = 0;

    /** @persistent */
    public $type;
    
    /** @persistent */
    public $invoiceId;
    
    public function createComponentAcceptedInvoicesGrid()
    {
        return $this->acceptedInvoiceControlFactory->create($this->user->id);
    }

    public function createComponentIssuedInvoicesGrid()
    {
        return $this->issuedInvoiceControlFactory->create($this->user->id);
    }

    public function renderShowDetail($id) {
        $invoice = $this->invoicesRepository->getInvoice($this->user->id,$id);

        if (!$invoice) {
            throw new Nette\Application\BadRequestException(404);
        }

        $this->template->invoice = $invoice;
    }

    public function renderAdd($type) {
        if ($type == "issued") {
            $count = $this->invoicesRepository->countTodayIssuedInvoices($this->user->id);
            
            if (strlen($count) <= 1) {
                $no = str_pad($count+1, strlen($count)+1,"0", STR_PAD_LEFT);
            }
            
            $defaults = Array("no"=>(date("Ymd").$no),"issuedDate"=>(date("Y-m-d")));
            $this['editInvoiceForm']->setDefaults($defaults);
        }
        
        $this->template->type = $type;
    }


    public function renderEdit($id)
    {
        $invoice = $this->invoicesRepository->getInvoiceArray($this->user->id,$id);
        
        $invoice['issuedDate'] = $invoice['issuedDate']->format('Y-m-d');
        $invoice['dueDate'] = $invoice['dueDate']->format('Y-m-d');
        $invoice['paymentDate'] = $invoice['paymentDate'] ? $invoice['paymentDate']->format('Y-m-d') : NULL;
        
        if ($invoice['type'] == "accepted") {
            $invoice['subjectAddressId'] = $invoice['supplier_address_id'];
        }
        else {
            $invoice['subjectAddressId'] = $invoice['customer_address_id'];
        }
        
        if($invoice['emailIssuedInvoiceText']) {
            $invoice['emailIssuedInvoice'] = TRUE;
        }
        
        $this['editInvoiceForm']->setDefaults($invoice);
        
        $this->template->invoice = $invoice;
    }

    protected function createComponentEditInvoiceForm()
    {
        $action = $this->getAction();

        $invoice = $this->invoicesRepository->getInvoiceArray($this->user->id,$this->getParameter("id"));

        if($invoice) {
            $type = $invoice['type'] == "issued" ? "customer" : "supplier";
        }
        else {
            $type = $this->getParameter("type") == "issued" ? "customer" : "supplier";
        }

        $subjectsPairs = $this->addressesRepository->getSubjectPairs($this->user->id, $type);

        if (!$invoice) {
            $invoice['type'] = $this->getParameter("type");
        }
        if ($invoice['type'] == "issued") {
            $subject = "Odběratel";
            $prompt = 'Vyberte odběratele';
        }
        elseif ($invoice['type'] == "accepted") {
            $subject = "Dodavatel";
            $prompt = 'Vyberte dodavatele';
        }
        else {
            throw new Nette\Application\BadRequestException;
        }

        if ($action == "edit" && !$invoice) {
            throw new Nette\Application\BadRequestException(404);
        }

        $form = new UI\Form;

        $form->addText("no","Číslo faktury")->setRequired("Číslo faktury je povinný údaj!");;
        $form->addSelect("subjectAddressId",$subject,$subjectsPairs)->setPrompt($prompt)->setRequired("Dodavatel je povinný údaj!");

        $paymentTypes = [
                        "bank_transfer" => "bankovním převodem",
                        "cash" => "hotově"
                        ];
        if($this->getParameter("type") == "issued") {
            $form->addSelect("paymentType", "Způsob úhrady", $paymentTypes)
                    ->setRequired()
                    ->setPrompt("-- Typ úhrady --");
        }

        $form->addText('issuedDate', 'Datum vystavení')
            ->setAttribute('data-provide', 'datepicker')
            ->setAttribute('data-date-orientation', 'bottom')
            ->setAttribute('data-date-format', 'yyyy-mm-dd')
            ->setAttribute('data-date-today-highlight', 'true')
            ->setAttribute('data-date-autoclose', 'true')
            ->setRequired("Datum je povinný údaj!")
            ->addRule($form::PATTERN, "Datum musí být ve formátu rrrr-mm-dd", "(19|20)\d\d\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])");

        $form->addText("dueDate","Datum splatnosti")
            ->setAttribute('data-provide', 'datepicker')
            ->setAttribute('data-date-orientation', 'bottom')
            ->setAttribute('data-date-format', 'yyyy-mm-dd')
            ->setAttribute('data-date-today-highlight', 'true')
            ->setAttribute('data-date-autoclose', 'true')
            ->setRequired("Datum je povinný údaj!")
            ->addRule($form::PATTERN, "Datum musí být ve formátu rrrr-mm-dd", "(19|20)\d\d\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])");

        $form->addText("paymentDate","Datum zaplacení")
                ->setAttribute('data-provide', 'datepicker')
                ->setAttribute('data-date-orientation', 'bottom')
                ->setAttribute('data-date-format', 'yyyy-mm-dd')
                ->setAttribute('data-date-today-highlight', 'true')
                ->setAttribute('data-date-autoclose', 'true')
                ->setRequired(FALSE)
                ->addRule($form::PATTERN, "Datum musí být ve formátu rrrr-mm-dd", "(19|20)\d\d\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])");

        if ($invoice['type'] == "issued") {
            $form->addCheckbox("emailIssuedInvoice","Vlastní text emailu")
                ->addCondition($form::EQUAL, TRUE)
                ->toggle('emailIssuedInvoiceText');

            $form->addTextArea('emailIssuedInvoiceText','Text emailu')
                ->setOption('id', 'emailIssuedInvoiceText')
                ->setAttribute('class', 'mceEditor')
                ->addConditionOn($form['emailIssuedInvoice'], UI\Form::EQUAL, TRUE)
                    ->setRequired('Zadejte text emailu');

            $form->addCheckbox("sent","Faktura byla již odeslána odběrateli");
        }

        if ($invoice['type'] == "accepted" && $this->getAction() == "add") {
            $form->addUpload("invoice_pdf", "Nahrát fakturu")
                ->setRequired(TRUE)
                ->addRule(UI\Form::MIME_TYPE, 'Faktura musí být v PDF.', 'application/pdf')
                ->addRule(UI\Form::MAX_FILE_SIZE, 'Maximální velikost souboru je 2 MB.', 2 * 1024 * 1024 /* v bytech */);
        }
        else {
            $form->addUpload("invoice_pdf", "Nahrát fakturu")
                ->setRequired(FALSE)
                ->addRule(UI\Form::MIME_TYPE, 'Faktura musí být v PDF.', 'application/pdf')
                ->addRule(UI\Form::MAX_FILE_SIZE, 'Maximální velikost souboru je 2 MB.', 2 * 1024 * 1024 /* v bytech */);
        }

        $form->addHidden("invoiceId", $this->getParameter("id"));
        $form->addHidden("type", $this->getParameter("type"));
        
        $form->addSubmit('edit', 'Upravit');

        $form->onSuccess[] = [$this, 'editInvoiceFormSucceeded'];

        $form->setRenderer(new \Instante\Bootstrap3Renderer\BootstrapRenderer);
        
        return $form;
    }

    public function editInvoiceFormSucceeded(UI\Form $form, $values)
    {
        $values['issuedDate'] = $values['issuedDate'] ? new \DateTime($values['issuedDate']) : NULL;
        $values['dueDate'] = $values['dueDate'] ? new \DateTime($values['dueDate']) : NULL;
        $values['paymentDate'] = $values['paymentDate'] ? new \DateTime($values['paymentDate']) : NULL;
        
        if($values['type'] == "issued") {
            $values['sent'] = $values['sent'] ? 1 : 0;
        }
        
        if($values->invoice_pdf->name) {
            /** @var Nette\Http\FileUpload **/
            $fileUpload = $values->invoice_pdf;
            $dir = __DIR__ . '/../../www/uploaded/';
            $filename = $fileUpload->move($dir . $values->type . '/'. $fileUpload->name)->name;
            $dir = 'uploaded/';

            $values->invoicePdfUrl = $dir . $values->type . '/'. $filename;
        }
        else {
            $values->invoicePdfUrl = NULL;
        }

        unset($values->invoice_pdf);

        $invoice = $this->invoicesService->saveInvoice($this->user->id,$values);

        $this->flashMessage('Faktura byla úspěšně uložena');

        $this->redirect('Invoice:showDetail',$invoice->id);
    }

    public function createComponentInvoiceDetailGrid($name) {
        $data = $this->invoicesRepository->fetchInvoiceDetail($this->user->id,$this->getParameter("id"));

        $grid = new DataGrid($this, $name);

        $grid->setDataSource($data);
        $grid->addColumnText('title', 'invoice_datagrid.title');

        $grid->addColumnText('quantity', 'invoice_datagrid.quantity');

        $grid->addColumnText('unit', 'invoice_datagrid.unit');

        $currency = ['Kč'];

        $grid->addColumnNumber('unitPrice', 'invoice_datagrid.unit_price')
            ->setRenderer(function ($row) use ($currency) {
                    if(is_double($row->unitPrice) && strlen(substr(strrchr($row->unitPrice, "."), 1)) > 0) {
                        $price = number_format($row->unitPrice, 2, ',', ' ');
                    }
                    else {
                        $price = number_format($row->unitPrice, 0, ',', ' ');
                    }
                    
                    return $price . ' ' . $currency[0];
                });

        $grid->addColumnNumber('price', 'invoice_datagrid.total')
            ->setRenderer(function ($row) use ($currency) {
                if(is_double($row->price) && strlen(substr(strrchr($row->price, "."), 1)) > 0) {
                    $price = number_format($row->price, 2, ',', ' ');
                }
                else {
                    $price = number_format($row->price, 0, ',', ' ');
                }

                return $price . ' ' . $currency[0];
            });

        $grid->addAction('delete', '', 'deleteInvoiceItem!',['invoiceItemId' => 'id', 'invoice'])
                ->setIcon('trash')
                ->setTitle('ublaboo_datagrid.delete')
                ->setClass('btn btn-xs btn-danger ajax')
                ->setConfirm('ublaboo_datagrid.confirm_deleting', 'title');

        $grid->addGroupAction('ublaboo_datagrid.delete')->onSelect[] = [$this, 'deleteInvoiceItems'];

        $grid->addInlineAdd()
            ->onControlAdd[] = function($container) {
		$container->addText('id', '')->setAttribute('readonly');
		$container->addText('title', '');
		$container->addText('quantity', '');
		$container->addText('unit', '');
                $container->addText('unitPrice', '');
                $container->addText('price', '')->setAttribute('readonly');
            };

        $p = $this;

        $grid->getInlineAdd()->onSubmit[] = function($values) use ($p) {
            $values->invoiceId = (int)$this->getParameter("id");

            $this->invoicesRepository->insertInvoiceItem($this->user->id,$values);

            $p->redrawControl("invoiceDetail");
            $p->flashMessage("Položka byla uložena", 'success');
            $this['invoiceDetailGrid']->reload();
            $p->redrawControl('flashes');
        };

        $grid->addInlineEdit()
            ->onControlAdd[] = function($container) {
		$container->addText('id', '')->setAttribute('readonly');
		$container->addText('title', '');
		$container->addText('quantity', '');
		$container->addText('unit', '');
                $container->addText('unitPrice', '');
                $container->addText('price', '')->setAttribute('readonly');
            };

        $grid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
            $container->setDefaults([
                'title' => $item->title,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unitPrice' => $item->unitPrice,
                'price' => $item->price,
            ]);
        };

        $grid->getInlineEdit()->onSubmit[] = function($id, $values) {
            $values->id = (int)$id;
            $values->invoiceId = (int)$this->getParameter("id");
            $this->invoicesRepository->updateInvoiceItem($this->user->id,$values);

            $this->redrawControl("invoiceDetail");
            $this->flashMessage("Položka byla upravena", 'success');
            $this->redrawControl('flashes');
        };

        $grid->setTranslator($this->translator);
    }

    public function handleDeleteInvoiceItem() {
        $this->flashMessage("Položka byla odstraněna!", 'danger');

        $invoiceId = $this->getParameter('id');
        $invoiceItemId = $this->getParameter('invoiceItemId');

        $this->invoicesRepository->deleteInvoiceItem($this->user->id,$invoiceId,$invoiceItemId);

        if ($this->isAjax()) {
            $this->redrawControl("invoiceDetail");
            $this->redrawControl('flashes');
            $this['invoiceDetailGrid']->reload();
        }
        else {
            $this->redirect('this');
        }
    }

    public function actionDeleteInvoice($id) {
        $this->invoicesRepository->deleteInvoice($this->user->id,$id);

        $this->flashMessage("Faktura byla odstraněna.","danger");
        $this->redirect('Invoice:');
    }

    public function deleteInvoiceItems(array $ids)
    {
        foreach ($ids as $iId) {
            $this->invoicesRepository->deleteInvoiceItem($this->user->id,$iId);
        }

	if ($this->isAjax()) {
            $this->redrawControl('flashes');
            $this->redrawControl("invoiceDetail");
            $this['invoiceDetailGrid']->reload();
	}
        else {
            $this->redirect('this');
	}

        $this->flashMessage("Položky byly smazány", 'danger');
    }

    public function actionPreviewPdf($id)
    {
        $invoice = $this->invoicesRepository->getInvoice($this->user->id,$id);

        if (!$invoice) {
            $this->error("Faktura nebyla nalezena!");
        }

        if ($invoice->type == "issued") {
            $file_content = @file_get_contents(realpath(__DIR__."/../../www")."/".$invoice->invoicePdfUrl);
            
            if ($invoice->invoicePdfUrl && $file_content) {
                $httpResponse = $this->context->getByType('Nette\Http\Response');

                $httpResponse->setHeader('Content-type','application/pdf');
                $httpResponse->setHeader('Content-Disposition','inline; filename="faktura.pdf"');
                $httpResponse->setHeader('Content-Transfer-Encoding','binary');
                $httpResponse->setHeader('Content-Length',filesize(realpath(__DIR__."/../../www")."/".$invoice->invoicePdfUrl));
                $httpResponse->setHeader('Accept-Ranges','bytes');

                $this->sendResponse(new Nette\Application\Responses\TextResponse($file_content));
            }
            else {
                $pdf = $this->createInvoicePDF($invoice);
                $pdf->setSaveMode(PdfResponse::INLINE);

                $this->sendResponse($pdf);
            }
        }
        else {
            $httpResponse = $this->context->getByType('Nette\Http\Response');
            $httpResponse->setCode(Nette\Http\Response::S403_FORBIDDEN);

            $this->flashMessage("Tato akce je povolená pouze u vydaných faktur");
        }
    }

    private function createInvoicePDF($invoice) {
        $template = $this->createTemplate();
        $template->setFile(__DIR__ . "/templates/pdf/previewInvoice.latte");

        $title = "Faktura č. ".$invoice->no;

        $template->title = $title;
        $template->invoice = $invoice;
        $template->payment_type = ($invoice->paymentType == "cash" ? "hotově" : "bankovním převodem");

        $qrPlatba = new QRPlatba();

        $qrPlatba->setAccount($invoice->supplierBankAccountNumber)
            ->setAmount($invoice->totalPrice)
            ->setVariableSymbol($invoice->no)
            ->setDueDate(new \DateTime());

        $template->qr = $qrPlatba->getQRCodeImage();

        $pdf = new PdfResponse($template);

        $pdf->documentTitle = $title; // creates filename 2012-06-30-my-super-title.pdf
        $pdf->getMPDF()->shrink_tables_to_fit = 1;
        $pdf->getMPDF()->setFooter("|Vygenováno systémem PP IS|{PAGENO} / {nb}"); // footer

        $pdf->setDocumentAuthor("PP IS");

        return $pdf;
    }

    public function actionShowUploadedPdf($id) {
        $invoice = $this->invoicesRepository->getInvoice($this->user->id,$id);

        if (!$invoice) {
            $this->error("Zadaná faktura neexuisuje");
        }

        if ($invoice->type == "accepted") {
            $httpResponse = $this->context->getByType('Nette\Http\Response');

            $httpResponse->setHeader('Content-type','application/pdf');
            $httpResponse->setHeader('Content-Disposition','inline; filename="faktura.pdf"');
            $httpResponse->setHeader('Content-Transfer-Encoding','binary');
            $httpResponse->setHeader('Content-Length',filesize(realpath(__DIR__."/../../www")."/".$invoice->invoicePdfUrl));
            $httpResponse->setHeader('Accept-Ranges','bytes');

            $this->sendResponse(new Nette\Application\Responses\TextResponse(file_get_contents(realpath(__DIR__."/../../www")."/".$invoice->invoicePdfUrl)));
        }
    }

    public function handleSendInvoice($id) {
        $invoice = $this->invoicesRepository->getInvoice($this->user->id, $id);
        
        if($invoice->type != "issued") {
            throw new Nette\Application\BadRequestException("Cannot send accepted invoice!");
        }

        $pdf = $this->createInvoicePDF($invoice);

        $dir = 'uploaded/issued/';
        $path = __DIR__ . '/../../www/' . $dir;
        $filename = "faktura-c-".$invoice->no;
        $savedFile = $pdf->save($path,$filename);

        $httpRequest = $this->context->getByType('Nette\Http\Request');
        $uri = $httpRequest->getUrl();

        $latte = new Latte\Engine;

        if($invoice->emailIssuedInvoiceText) {
            $params['text'] = $invoice->emailIssuedInvoiceText;
        }
        else {
            $params['invoice'] = $invoice;
            $params['text'] = $invoice->customerAddress->emailIssuedInvoiceTemplate;
        }

        $emailFrom = $this->settingsRepository->getSettings("email")->value;
        $emailFromSubject = $invoice->supplierSubject;
        $emailTo = $invoice->customerEmail;
        $emailReplyTo = $this->usersRepository->findById($this->user->id)->email;
        $emailReplyToSubject = $invoice->supplierSubject;
        $emailBcc = $this->usersRepository->findById($this->user->id)->email;

        $mail = new Nette\Mail\Message;

        $mail->setFrom($emailFrom,$emailFromSubject);
        $mail->addTo($emailTo);
        $mail->addReplyTo($emailReplyTo,$emailReplyToSubject);
        $mail->addBcc($emailBcc);
        $mail->setSubject("Faktura č.".$invoice->no);
        $mail->setHtmlBody($latte->renderToString(__DIR__.'/templates/email/issued-invoice.latte', $params));
        $mail->addAttachment($savedFile);

        $this->mailer->send($mail);

        $this->invoicesRepository->saveInvoice($this->user->id, Nette\Utils\ArrayHash::from(['invoiceId'=>$id,'invoicePdfUrl'=>$dir.$filename.'.pdf','sent'=>1]));

        $this->flashMessage("Vydaná faktura byla úspěšně uložena a odeslána odběrateli na mail","success");

        if(!$this->isAjax()) {
            $this->redirect("Invoice:showDetail",$id);
        }
        else {
            $this->redrawControl('flashes');
        }
    }

    public function renderEditPayment($id,$invoiceId) {
        $invoice = $this->invoicesRepository->getInvoice($this->user->id, $invoiceId);
        
        $this->template->invoice = $invoice;
        
        $payment = $this->paymentsRepository->getPaymentArray($id);
        
        if(!$payment) {
            $this->error("Platba neexistuje!",404);
        }
        
        $payment["paymentType"] = $payment['paymentType'] == "" ? NULL : $payment['paymentType'];
        $payment["taxType"] = $payment['taxType'] ? 1 : 0;
        $payment["paymentDate"] = $payment['paymentDate'] ? $payment['paymentDate']->format('Y-m-d') : NULL;
        $payment["rounded_amount"] = floor($payment['amount']);
        $payment["cents"] = round(($payment["amount"] - $payment["rounded_amount"]),2) * 100;
        
        $this['editPaymentInvoiceForm']->setDefaults($payment);
    }
    
    public function createComponentInvoicePaymentsGrid($name) {
        $invoice = $this->invoicesRepository->getInvoice($this->user->id, $this->presenter->getParameter("id"));
        
        $data = $this->paymentsRepository->fetchPaymentsDataSource($invoice->no);
        
        $grid = new DataGrid($this, $name);
        
        $grid->setDataSource($data);
        
        $grid->addColumnDateTime("paymentDate", "payments_datagrid.payment_date");
        $grid->addColumnText("description", "payments_datagrid.description");
        $grid->addColumnText("paymentType", "payments_datagrid.paymentType")
            ->setRenderer(function ($row) {
                if($row->paymentType == "bank_transfer") {
                    return "převodem z účtu / kartou";
                }
                elseif($row->paymentType == "cash") {
                    return "hotově";
                }
                elseif($row->paymentType == "paypal") {
                    return "platba přes PayPal";
                }
                else {
                    return;
                }
            });
        $grid->addColumnText("taxType", "payments_datagrid.taxType")
            ->setRenderer(function ($row) {
                if($row->taxType == 0) {
                    return "daňově neuznatelný";
                }
                elseif($row->taxType == 1) {
                    return "daňově uznatelný";
                }
            });
        
        $currency = ['Kč'];

        $grid->addColumnNumber('amount', 'payments_datagrid.amount')
            ->setRenderer(function ($row) use ($currency) {
                if(is_double($row->amount) && strlen(substr(strrchr($row->amount, "."), 1)) > 0) {
                    $price = number_format($row->amount, 2, ',', ' ');
                }
                else {
                    $price = number_format($row->amount, 0, ',', ' ');
                }

                return $price . ' ' . $currency[0];
            });
        
        $grid->addAction('editPayment', 'Upravit')
            ->setRenderer(function($item) {
                $link = $this->presenter->link("Invoice:editPayment",[$item->id,'invoiceId'=>$this->presenter->getParameter("id")]);
                
                return '<a href="'.$link.'" class="btn btn-xs btn-default"><span class="fa fa-pencil"></span> Upravit</a>';
            });
        
        $grid->addAction('delete', '', 'delete!',['paymentId'=>'id'])
		->setIcon('trash')
		->setTitle('ublaboo_datagrid.delete')
		->setClass('btn btn-xs btn-danger ajax')
		->setConfirm('ublaboo_datagrid.confirm_deleting', 'description');
        
        $grid->setTranslator($this->translator);
    }
    
    protected function createComponentEditPaymentInvoiceForm()
    {
        $invoice = $this->invoicesRepository->getInvoice($this->user->id, $this->getParameter("invoiceId"));
        
        $payments = $this->paymentsRepository->fetchPayments($invoice->no);
        
        $id = $this->getParameter("id");
        $type = $invoice->type == "issued" ? "income" : "expense";
        
        $amounts = [];  
        
        foreach ($payments as $payment) {
            $amounts[$payment->id] = $payment->amount;
        }
        unset($amounts[$id]);
        
        $amountSum = array_sum($amounts);
        
        $balancePayment = round(($invoice->totalPrice - $amountSum) * 100) / 100;
        
        $form = new UI\Form;
        
        if ($balancePayment > 0) {
            if($id) {
                $form->addInteger('rounded_amount', 'Částka')
                    ->setHtmlAttribute("max", $balancePayment)
                    ->setRequired()
                    ->addRule($form::RANGE, "Částka nesmí přesáhnout nesplacenou částku a nesmí být nulová", [1, $balancePayment]);
            
                $form->addInteger('cents', 'h.')
                    ->setHtmlAttribute("max", 99)
                    ->setRequired()
                    ->addRule($form::RANGE, "Částka nesmí přesáhnout maximální výši haléřů", [0, 99]);
            }
            else {
                $form->addInteger('rounded_amount', 'Částka')
                    ->setHtmlAttribute("max", $balancePayment)
                    ->setRequired()
                    ->addRule($form::RANGE, "Částka nesmí přřesáhnout nesplacenou částku a nesmí být nulová", [1, $balancePayment])
                    ->setDefaultValue(0);
                $form->addInteger('cents', 'h.')
                    ->setHtmlAttribute("max", 99)
                    ->setRequired()
                    ->addRule($form::RANGE, "Částka nesmí přřesáhnout 99 haléřů", [0, 99])
                    ->setDefaultValue(0);
            }
            
            $form->addText('left_amount', 'Zbývá uhradit')->setDisabled();
            $form->addText('entire_amount', 'Celková neúčtovaná částka k úhradě')->setDisabled()->setDefaultValue($balancePayment);
            
            $label = "Způsob úhrady";
            $items = [
                        "cash" => "hotově",
                        "bank_transfer" => "převodem z účtu / kartou",
                        "paypal" => "platba přes PayPal"
                     ];
            $form->addSelect("paymentType", $label, $items)
                    ->setPrompt("-- Vyberte ".strtolower($label)." --")
                    ->setRequired();
            
            if($invoice->type == "issued") {
                $label = "Typ příjmu";
            }
            elseif($invoice->type == "accepted") {
                $label = "Typ výdaje";
            }
            
            $items = ["0"=>"daňově neuznatelný","1"=>"daňově uznatelný"];
            $form->addSelect("taxType", $label, $items)
                    ->setPrompt("-- Vyberte ".strtolower($label)." --")
                    ->setRequired();
            $form->addText("description","Popis platby")
                    ->setRequired();
            $form->addText("paymentDate","Datum zaplacení")
                    ->setAttribute('data-provide', 'datepicker')
                    ->setAttribute('data-date-orientation', 'bottom')
                    ->setAttribute('data-date-format', 'yyyy-mm-dd')
                    ->setAttribute('data-date-today-highlight', 'true')
                    ->setAttribute('data-date-autoclose', 'true')
                    ->setRequired(TRUE)
                    ->addRule($form::PATTERN, "Datum musí být ve formátu rrrr-mm-dd", "(19|20)\d\d\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])");
            $form->addHidden("type",$type);
            $form->addHidden("paymentId",$id);
            $form->addHidden("documentId",$invoice->no);
            $form->addSubmit('save', 'Uložit');
        }
        else {
            $form->addText('left_amount', 'Zbývá uhradit')
                    ->setDisabled()
                    ->setValue("Celá částka již byla uhrazena");
        }
        
        $form->onValidate[] = [$this, 'validateEditPaymentInvoiceForm'];
        
        $form->onSuccess[] = [$this, 'editPaymentInvoiceFormSucceeded'];
        
        $form->setRenderer(new \Instante\Bootstrap3Renderer\BootstrapRenderer);
        
        return $form;
    }
    
    public function validateEditPaymentInvoiceForm($form) {
        $values = $form->getValues();
        
        $invoice = $this->invoicesRepository->getInvoice($this->user->id, $this->getParameter("invoiceId"));
        $payments = $this->paymentsRepository->fetchPayments($invoice->no);
        
        $id = $this->getParameter("id");
        
        $amounts = [];  
        
        foreach ($payments as $payment) {
            $amounts[$payment->id] = $payment->amount;
        }
        
        unset($amounts[$id]);
        
        $amountSum = array_sum($amounts);
        
        $balancePayment = round(($invoice->totalPrice - $amountSum) * 100) / 100;
        $new_amount = $values->rounded_amount + $values->cents / 100; 
        
        if ($new_amount > $balancePayment) {
            $form->addError('Částka nesmí přřesáhnout nesplacenou částku');
        }
    }
    
    public function editPaymentInvoiceFormSucceeded(UI\Form $form, $values) {
        $userId = $this->user->id;
        $paymentId = $values->paymentId;
        
        unset($values['paymentId']);
        $values['paymentDate'] = $values['paymentDate'] ? new \DateTime($values['paymentDate']) : NULL;
        $values['cents'] = $values['cents'] / 100;
        $values['amount'] = $values['rounded_amount'] + $values['cents'];
        
        unset($values['rounded_amount']);
        unset($values['cents']);
        
        $this->paymentsRepository->savePayment($userId,$paymentId,$values);
        
        $this->flashMessage('Platba byla úspěšně zaúčtována','success');
        
        $this->redirect('Invoice:showDetail', ['id'=>$this->getParameter("invoiceId"),"invoiceId"=>NULL]);
    }
    
    public function handleDelete($paymentId) {
        $payment = $this->paymentsRepository->getPaymentArray($paymentId);
        
        if ($payment) {
            $this->paymentsRepository->deletePayment($this->user->id,$paymentId);
            
            $this->flashMessage("Položka byla odstraněna!", 'danger');
            
            if ($this->isAjax()) {
                $this->redrawControl('flashes');
                $this['invoicePaymentsGrid']->reload();
            }
            else {
                $this->redirect('this');
            }
        }
        else {
            throw new Nette\Application\BadRequestException("Položka nebyla nalezena");
        }
    }
}