<?php
namespace CjsConsole\Helper;

use CjsConsole\Contracts\DescriptorInterface;
use CjsConsole\Descriptor\JsonDescriptor;
use CjsConsole\Descriptor\MarkdownDescriptor;
use CjsConsole\Descriptor\TextDescriptor;
use CjsConsole\Descriptor\XmlDescriptor;

class DescriptorHelper extends Helper
{

    private $descriptors = array();

    public function __construct()
    {
        $this
            ->register('txt', new TextDescriptor())
            ->register('xml', new XmlDescriptor())
            ->register('json', new JsonDescriptor())
            ->register('md', new MarkdownDescriptor())
        ;
    }

    /**
     * Describes an object if supported.
     *
     * Available options are:
     * * format: string, the output format name
     * * raw_text: boolean, sets output type as raw
     *
     * @param OutputInterface $output
     * @param object          $object
     * @param array           $options
     *
     * @throws \InvalidArgumentException when the given format is not supported
     */
    public function describe($output, $object, array $options = array())
    {
        $options = array_merge(array(
            'raw_text' => false,
            'format' => 'txt',
        ), $options);

        if (!isset($this->descriptors[$options['format']])) {
            throw new \InvalidArgumentException(sprintf('Unsupported format "%s".', $options['format']));
        }

        $descriptor = $this->descriptors[$options['format']];
        $descriptor->describe($output, $object, $options);
    }

    /**
     * Registers a descriptor.
     *
     * @param string              $format
     * @param DescriptorInterface $descriptor
     *
     * @return DescriptorHelper
     */
    public function register($format, DescriptorInterface $descriptor)
    {
        $this->descriptors[$format] = $descriptor;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'descriptor';
    }
}
