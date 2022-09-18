<?php
/**
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 * Copyright (C) 2019 - 2020 Jan Böhmer (https://github.com/jbtronics)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

/**
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 * Copyright (C) 2019 Jan Böhmer (https://github.com/jbtronics)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 */

namespace App\Form\Type;

use App\Entity\Parts\MeasurementUnit;
use App\Services\SIFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

final class SIUnitType extends AbstractType implements DataMapperInterface
{
    protected SIFormatter $si_formatter;

    public function __construct(SIFormatter $SIFormatter)
    {
        $this->si_formatter = $SIFormatter;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'measurement_unit' => null,
            'show_prefix' => static function (Options $options) {
                if (null !== $options['measurement_unit']) {
                    /** @var MeasurementUnit $unit */
                    $unit = $options['measurement_unit'];

                    return $unit->isUseSIPrefix();
                }

                return false;
            },
            'is_integer' => static function (Options $options) {
                if (null !== $options['measurement_unit']) {
                    /** @var MeasurementUnit $unit */
                    $unit = $options['measurement_unit'];

                    return $unit->isInteger();
                }

                return false;
            },
            'unit' => static function (Options $options) {
                if (null !== $options['measurement_unit']) {
                    /** @var MeasurementUnit $unit */
                    $unit = $options['measurement_unit'];

                    return $unit->getUnit();
                }

                return null;
            },
            'error_mapping' => [
                '.' => 'value',
            ],
        ]);

        $resolver->setAllowedTypes('measurement_unit', [MeasurementUnit::class, 'null']);
        $resolver->setRequired('unit');

        //Options which allows us, to limit the input using HTML5 number input
        $resolver->setDefaults([
            'min' => 0,
            'max' => '',
            'step' => static function (Options $options) {
                if (true === $options['is_integer']) {
                    return 1;
                }

                return 'any';
            },
            'html5' => true,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('value', NumberType::class, [
                'label' => false,
                'html5' => $options['html5'],
                'attr' => [
                    'min' => (string) $options['min'],
                    'max' => (string) $options['max'],
                    'step' => (string) $options['step'],
                ],
            ]);

        if ($options['show_prefix']) {
            $builder->add('prefix', ChoiceType::class, [
                'label' => 'false',
                'choices' => [
                    'M' => 6,
                    'k' => 3,
                    '' => 0,
                    'm' => -3,
                    'µ' => -6,
                ],
                'choice_translation_domain' => false,
            ]);
        }

        $builder->setDataMapper($this);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['sm'] = false;

        //Check if we need to make this thing small
        if (isset($options['attr']['class'])) {
            $view->vars['sm'] = false !== strpos($options['attr']['class'], 'form-control-sm');
        }

        $view->vars['unit'] = $options['unit'];
        parent::buildView($view, $form, $options); // TODO: Change the autogenerated stub
    }

    /**
     * Maps the view data of a compound form to its children.
     *
     * The method is responsible for calling {@link FormInterface::setData()}
     * on the children of compound forms, defining their underlying model data.
     *
     * @param mixed                       $viewData View data of the compound form being initialized
     * @param FormInterface[]|Traversable $forms    A list of {@link FormInterface} instances
     *
     * @throws Exception\UnexpectedTypeException if the type of the data parameter is not supported
     */
    public function mapDataToForms($viewData, $forms): void
    {
        $forms = iterator_to_array($forms);

        if (null === $viewData) {
            if (isset($forms['prefix'])) {
                $forms['prefix']->setData(0);
            }

            return;
        }

        $data = $this->si_formatter->convertValue((float) $viewData);

        if (isset($forms['prefix'])) {
            $forms['value']->setData($data['value']);
            $forms['prefix']->setData($data['prefix_magnitude']);
        } else {
            $forms['value']->setData($viewData);
        }
    }

    /**
     * Maps the model data of a list of children forms into the view data of their parent.
     *
     * This is the internal cascade call of FormInterface::submit for compound forms, since they
     * cannot be bound to any input nor the request as scalar, but their children may:
     *
     *     $compoundForm->submit($arrayOfChildrenViewData)
     *     // inside:
     *     $childForm->submit($childViewData);
     *     // for each entry, do the same and/or reverse transform
     *     $this->dataMapper->mapFormsToData($compoundForm, $compoundInitialViewData)
     *     // then reverse transform
     *
     * When a simple form is submitted the following is happening:
     *
     *     $simpleForm->submit($submittedViewData)
     *     // inside:
     *     $this->viewData = $submittedViewData
     *     // then reverse transform
     *
     * The model data can be an array or an object, so this second argument is always passed
     * by reference.
     *
     * @param FormInterface[]|Traversable $forms    A list of {@link FormInterface} instances
     * @param mixed                       $viewData The compound form's view data that get mapped
     *                                              its children model data
     *
     * @throws Exception\UnexpectedTypeException if the type of the data parameter is not supported
     */
    public function mapFormsToData($forms, &$viewData): void
    {
        //Convert both fields to a single float value.

        $forms = iterator_to_array($forms);

        $viewData = $forms['value']->getData();

        if (isset($forms['prefix'])) {
            $multiplier = $forms['prefix']->getData();
            $viewData *= 10 ** $multiplier;
        }
    }
}
