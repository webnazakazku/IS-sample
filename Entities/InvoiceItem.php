<?php
declare(strict_types=1);

namespace App\Entities;

use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repositories\InvoiceItemRepository")
 * @ORM\Table(name="invoice_items")
 */
class InvoiceItem
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $title;

	/**
	 * @ORM\ManyToOne(targetEntity="Invoice", inversedBy="items")
	 * @ORM\JoinColumn(name="invoice_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
	 */
	private $invoice;

	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2, nullable=false)
	 */
	private $quantity;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 * @var string
	 */
	private $unit;

	/**
	 * @ORM\Column(type="decimal", precision=9, scale=2, nullable=false)
	 */
	private $unitPrice;

	/**
	 * @var int
	 */
	private $price;

	public function getId()
	{
		return $this->id;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function getInvoice()
	{
		return $this->invoice;
	}

	public function getQuantity()
	{
		return $this->quantity;
	}

	public function getUnit()
	{
		return $this->unit;
	}

	public function getUnitPrice()
	{
		return (float) $this->unitPrice;
	}

	public function getPrice()
	{
		$price = $this->quantity * $this->unitPrice;

		return $price;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function setTitle($title)
	{
		$this->title = $title;
	}

	public function setInvoice($invoice)
	{
		$this->invoice = $invoice;
	}

	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;
	}

	public function setUnit($unit)
	{
		$this->unit = $unit;
	}

	public function setUnitPrice($unitPrice)
	{
		$this->unitPrice = $unitPrice;
	}
}
