<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\GaNetBundle\Repository\PublisherRepository;

#[ORM\Entity(repositoryClass: PublisherRepository::class)]
#[ORM\Table(name: 'ga_net_publisher', options: ['comment' => 'GA Net 发布商表'])]
class Publisher implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\Column(name: 'publisher_id', type: Types::INTEGER, options: ['comment' => '账户id'])]
    #[ORM\CustomIdGenerator]
    #[Assert\Positive]
    private ?int $publisher_id = null;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => 'API Token'])]
    #[Assert\Length(max: 100)]
    #[Assert\NotBlank]
    private ?string $token = null;

    /**
     * @var Collection<int, Campaign>
     */
    #[ORM\OneToMany(mappedBy: 'publisher', targetEntity: Campaign::class, cascade: ['persist'])]
    private Collection $campaigns;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(mappedBy: 'publisher', targetEntity: Transaction::class, cascade: ['persist'])]
    private Collection $transactions;

    /**
     * @var Collection<int, PromotionCampaign>
     */
    #[ORM\OneToMany(mappedBy: 'publisher', targetEntity: PromotionCampaign::class, cascade: ['persist'])]
    private Collection $promotionCampaigns;

    /**
     * @var Collection<int, RedirectTag>
     */
    #[ORM\OneToMany(mappedBy: 'publisher', targetEntity: RedirectTag::class, cascade: ['persist'])]
    private Collection $redirectTags;

    public function __construct()
    {
        $this->campaigns = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->promotionCampaigns = new ArrayCollection();
        $this->redirectTags = new ArrayCollection();
    }

    public function getPublisherId(): ?int
    {
        return $this->publisher_id;
    }

    public function setPublisherId(?int $publisherId): void
    {
        $this->publisher_id = $publisherId;
    }

    public function getId(): ?int
    {
        return $this->publisher_id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return Collection<int, Campaign>
     */
    public function getCampaigns(): Collection
    {
        return $this->campaigns;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    /**
     * @return Collection<int, PromotionCampaign>
     */
    public function getPromotionCampaigns(): Collection
    {
        return $this->promotionCampaigns;
    }

    /**
     * @return Collection<int, RedirectTag>
     */
    public function getRedirectTags(): Collection
    {
        return $this->redirectTags;
    }

    public function generateSign(int $timestamp): string
    {
        return md5(($this->publisher_id ?? 0) . $timestamp . ($this->token ?? ''));
    }

    public function __toString(): string
    {
        return (string) ($this->publisher_id ?? 0);
    }
}
