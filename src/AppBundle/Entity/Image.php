<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Image
 *
 * @ORM\Table(name="image")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ImageRepository")
 * @Vich\Uploadable
 */
class Image
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
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var bool
     *
     * @ORM\Column(name="isStar", type="boolean")
     */
    private $isStar;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @Assert\Length(min=3)
     */
    private $name;
    
    /**
     * @Vich\UploadableField(mapping="image_product", fileNameProperty="name")
     * @Assert\File(
     *      maxSize="2M",
     *      mimeTypes={"image/jpeg","image/png"},
     *      mimeTypesMessage= "Choose a valid image file (jpg,png) ")
     *
     * @var File
    */
    private $imageFile;


    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Product", inversedBy="images")
     * @ORM\JoinColumn(nullable=false)
    */
    private $product;


    public function _construct()
    {
        $this->date= new \DateTime();
    }

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
     * @return Image
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
     * Set isStar
     *
     * @param boolean $isStar
     *
     * @return Image
     */
    public function setIsStar($isStar)
    {
        $this->isStar = $isStar;

        return $this;
    }

    /**
     * Get isStar
     *
     * @return bool
     */
    public function getIsStar()
    {
        return $this->isStar;
    }


     /**
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $img
     *
     * @return Image
    */
    public function setImageFile(File $img = null)
    {
        $this->imageFile = $img;
        if($img){
            $this->date = new\DateTimeImmutable();
        }
        return $this;
    }
    /**
     * @return File|null
    */
    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Image
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set product
     *
     * @param \AppBundle\Entity\Product $product
     *
     * @return Image
     */
    public function setProduct(\AppBundle\Entity\Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \AppBundle\Entity\Product
     */
    public function getProduct()
    {
        return $this->product;
    }
}
