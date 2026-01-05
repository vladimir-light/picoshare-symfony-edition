<?php

namespace App\Form;

use App\Entity\GuestLink;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as FormTypes;
use Symfony\Component\Validator\Constraints as Assert;

class GuestLinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', FormTypes\TextType::class, [
                'label' => 'Label (optional)',
                'required' => false,
            ])
            ->add('link_expires_after', FormTypes\ChoiceType::class, [
                'label' => 'Guest Link Expires',
                'mapped' => false,
                'required' => true,
                'choices' => $this->prepareLinkExpiresAfterChoices(),
                'property_path' => 'expiresAt',
            ])
            ->add('file_expiration', FormTypes\ChoiceType::class, [
                'label' => 'Guest Files Expire',
                'help' => 'This value will be the Default "Expiration" value in upload-form',
                'required' => true,
                'choices' => $this->prepareFileExpirationChoices(),
                'property_path' => 'fileExpiration',
            ])
            ->add('max_file_size', FormTypes\NumberType::class, [
                'label' => 'Max file size (optional)',
                'required' => false,
                'attr' => [
                    'placeholder' => '20MB',
                    'min' => 1,
                    'step' => 1,
                ],
                'property_path' => 'maxFileSizeInMegaBytes', // special getter/setter in GuestLink entity
                'html5' => true,
                'help' => 'Lorem Ipsum',
            ])
            ->add('max_uploads', FormTypes\NumberType::class, [
                'label' => 'Upload limit (optional)',
                'required' => false,
                'attr' => [
                    'placeholder' => '40',
                    'min' => 1,
                    'step' => 1,
                ],
                'property_path' => 'maxUploads',
                'html5' => true,
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmitEventHandler']);
    }

    public function postSubmitEventHandler(FormEvent $postSubmitEv): void
    {
        $form = $postSubmitEv->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var GuestLink $entity */
        $entity = $postSubmitEv->getData();

        // Normalizing input
        $chosenLinkExpiration = $form->get('link_expires_after')->getData();
        // null -> never
        if( $chosenLinkExpiration !== null )
        {
            $refNow = new \DateTimeImmutable('today');
            $expirationModifier = str_replace('-', ' ', $chosenLinkExpiration);
            $newExpirationDateTime = $refNow->modify($expirationModifier);
            $entity->setExpiresAt($newExpirationDateTime);
        }
//        // 2 - maxFileSize -> convert input (in MB) to int as bytes, since this value is stored in DB
//        $maxFileSize = $form->get('max_file_size')->getData();
//        // null -> unlimited
//        if(!empty($maxFileSize) )
//        {
//            $entity->setMaxFileBytes( $maxFileSize * 1024 * 1024 );
//        }


        // FIXME: Debug-purpose only
        //$form->addError(new FormError(__METHOD__ . ' momentski...'));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GuestLink::class,
        ]);
    }

    private function prepareLinkExpiresAfterChoices(): array
    {
        return self::defaultExpirationChoices();
    }

    private function prepareFileExpirationChoices(): array
    {
        return self::defaultExpirationChoices();
    }

    /**
     * @return list<string, string|null>
     */
    private static function defaultExpirationChoices(): array
    {
        // TODO: Extract into Separate class/enum to prevent redundancy
        return [
            '1 day' => '1-day',
            '7 days' => '7-day',
            '30 days' => '30-day',
            '1 year' => '1-year',
            'Never' => null,
        ];
    }
}
