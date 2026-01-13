<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as FormTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UploadFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $addNoteField = $options['is_guest_link'] === false;
        $preselectedExpiration = $options['preselected_auto_expire_value'];
        // for all...even for admins, who have "unlimited"
        $maxFilesize = '512'; // TODO: remove after fixing memory-issues with huge files
        if(null !== $options['custom_max_upload_filesize_in_mb'])
        {
            // this option is only set (not null) when GuestLink has limited uploadFileSize
            $maxFilesize = $options['custom_max_upload_filesize_in_mb'];
        }

        $builder
            ->add('file', FormTypes\FileType::class, [
                'label' => 'Choose a file',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File(
                        maxSize: $maxFilesize . 'M',
                        binaryFormat: false
                    )
                ],
                'help' => 'Max. filesize is: ' . $maxFilesize . ' MB',
            ]);
        $builder->add('auto_expire', FormTypes\ChoiceType::class, [
            'label' => 'Expiration',
            'choices' => self::defaultExpirationChoices(),
            'data' => $preselectedExpiration, // null -> never
            'required' => true,
            'mapped' => false,
        ]);


        if ($addNoteField) {
            $builder->add('note', FormTypes\TextareaType::class, [
                'label' => 'note',
                'help' => 'Note is only visible to you',
                'required' => false,
            ]);
        }

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
            'is_guest_link' => false,
            'preselected_auto_expire_value' => null,
            'custom_max_upload_filesize_in_mb' => null,
        ]);
        $resolver->setAllowedTypes('is_guest_link', ['bool']);
        $resolver->setAllowedTypes('preselected_auto_expire_value', ['string', 'null']);
        $resolver->setAllowedTypes('custom_max_upload_filesize_in_mb', ['int', 'null']);
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
            'Custom' => -1,
        ];
    }
}
