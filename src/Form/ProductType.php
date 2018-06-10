<?php
/**
 * Created by PhpStorm.
 * User: agalempaszek
 * Date: 20.05.2018
 * Time: 13:03
 */

namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ProductType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add(
			'name',
			TextType::class,
			[
				'label' => 'label.name',
				'required' => true,
				'attr' => [
					'max_length' => 128,
				]
			]
		);
		$builder->add(
			'value',
			NumberType::class,
			[
				'label' => 'label.value',
				'required' => false,
				'scale' => 2,
			]
		);
		$builder->add(
			'quantity',
			NumberType::class,
			[
				'label' => 'label.quantity',
				'required' => false,
				'scale' => 1,
			]
		);
		$builder->add(
			'isBought',
			ChoiceType::class,
			[
				'label' => 'label.isBought',
				'required' => true,
				'choices' => [
					'label.yes' => 1,
					'label.no' => 0,
				],
			]
		);
	}

	public function getBlockPrefix() {
		return 'product_type';
	}

}