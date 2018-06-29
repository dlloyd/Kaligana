<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Product
 *
 * @ORM\Table(name="product")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProductRepository")
 */
class Product
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\ProductType", inversedBy="products")
     * @ORM\JoinColumn(nullable=false)
    */
    private $productType;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="decimal", scale=2)
     */
    private $priceUnit;

    /**
     * @var string
     *
     * @ORM\Column(name="durationType", type="string", length=255)
     */
    private $durationType; //  heure(s) or jour(s) 

    /**
     * @var int
     *
     * @ORM\Column(name="durationNumber", type="integer")
     */
    private $durationNumber;  

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Image", mappedBy="product")
    */
    private $images;


    /**
     * @var string
     *
     * @ORM\Column(name="img_star_name", type="string", length=255)
     */
    private $imgStarName;  //Permet de ne pas charger toutes les images pour afficher la vedette


    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Reservation", mappedBy="product")
    */
    private $reservations;  


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Product
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Product
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    

    /**
     * Set durationType
     *
     * @param string $durationType
     *
     * @return Product
     */
    public function setDurationType($durationType)
    {
        $this->durationType = $durationType;

        return $this;
    }

    /**
     * Get durationType
     *
     * @return string
     */
    public function getDurationType()
    {
        return $this->durationType;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->images = new \Doctrine\Common\Collections\ArrayCollection();
        $this->reservations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add image
     *
     * @param \AppBundle\Entity\Image $image
     *
     * @return Product
     */
    public function addImage(\AppBundle\Entity\Image $image)
    {
        $this->images[] = $image;

        return $this;
    }

    /**
     * Remove image
     *
     * @param \AppBundle\Entity\Image $image
     */
    public function removeImage(\AppBundle\Entity\Image $image)
    {
        $this->images->removeElement($image);
    }

    /**
     * Get images
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * Add reservation
     *
     * @param \AppBundle\Entity\Reservation $reservation
     *
     * @return Product
     */
    public function addReservation(\AppBundle\Entity\Reservation $reservation)
    {
        $this->reservations[] = $reservation;

        return $this;
    }

    /**
     * Remove reservation
     *
     * @param \AppBundle\Entity\Reservation $reservation
     */
    public function removeReservation(\AppBundle\Entity\Reservation $reservation)
    {
        $this->reservations->removeElement($reservation);
    }

    /**
     * Get reservations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReservations()
    {
        return $this->reservations;
    }

    /**
     * Set imgStarName
     *
     * @param string $imgStarName
     *
     * @return Product
     */
    public function setImgStarName($imgStarName)
    {
        $this->imgStarName = $imgStarName;

        return $this;
    }

    /**
     * Get imgStarName
     *
     * @return string
     */
    public function getImgStarName()
    {
        return $this->imgStarName;
    }

    /**
     * Set productType
     *
     * @param \AppBundle\Entity\ProductType $productType
     *
     * @return Product
     */
    public function setProductType(\AppBundle\Entity\ProductType $productType)
    {
        $this->productType = $productType;

        return $this;
    }

    /**
     * Get productType
     *
     * @return \AppBundle\Entity\ProductType
     */
    public function getProductType()
    {
        return $this->productType;
    }

    /**
     * Set priceUnit
     *
     * @param string $priceUnit
     *
     * @return Product
     */
    public function setPriceUnit($priceUnit)
    {
        $this->priceUnit = $priceUnit;

        return $this;
    }

    /**
     * Get priceUnit
     *
     * @return string
     */
    public function getPriceUnit()
    {
        return $this->priceUnit;
    }

    /**
     * Set durationNumber
     *
     * @param integer $durationNumber
     *
     * @return Product
     */
    public function setDurationNumber($durationNumber)
    {
        $this->durationNumber = $durationNumber;

        return $this;
    }

    /**
     * Get durationNumber
     *
     * @return integer
     */
    public function getDurationNumber()
    {
        return $this->durationNumber;
    }
}
