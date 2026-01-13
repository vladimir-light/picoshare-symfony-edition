<?php

namespace App\Form;

use App\Entity\Entry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as FormTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class EntryType extends AbstractType
{
    public const FIELD_EXPIRES_AFTER = 'delete_after_expiration';
    private const FIELD_FILENAME = 'filename';
    public const FIELD_EXPIRES_AT_DATE = 'expires_at';
    private const FIELD_NOTE = 'note';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(self::FIELD_FILENAME)
            ->add(self::FIELD_EXPIRES_AFTER, FormTypes\CheckboxType::class, [
                'label' => 'Delete after expiration',
                'mapped' => false,
                'data' => true,
            ])
            ->add(self::FIELD_EXPIRES_AT_DATE, FormTypes\DateType::class, [
                'html5' => true,
                'required' => false,
                'property_path' => 'expiresAt',
                'help' => 'After submit, the real expiration DateTime will be set to 23:59:59 of previous date',
                'constraints' => [
                    //TODO: If self::FIELD_EXPIRES_AFTER is true, then self::FIELD_EXPIRES_AT_DATE must be a valid datetime
                    // new Assert\DateTime(),
                    new Assert\GreaterThanOrEqual(value: 'today UTC')
                ],
                'attr' => [
                    'min' => (new \DateTime('today UTC'))->format('Y-m-d')
                ]
            ])
            ->add(self::FIELD_NOTE, FormTypes\TextareaType::class, [
                'label' => 'Note',
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(allowNull: true),
                ]
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmitEventHandler']);
    }

    public function postSubmitEventHandler(FormEvent $postSubmitEv): void
    {
        $form = $postSubmitEv->getForm();
        if (!$form->isValid()) {
            return;
        }

        // $form->addError(new FormError(__METHOD__ . ' hold on...')); // FIXME: Debugging-purpose only
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Entry::class,
        ]);
    }
}
