<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Normalizer;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

/**
 * Normalizes an object implementing the {@see \DateTimeInterface} to a date string.
 * Denormalizes a date string to an instance of {@see \DateTime} or {@see \DateTimeImmutable}.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DateTimeNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    const FORMAT_KEY = 'datetime_format';
    const TIMEZONE_KEY = 'datetime_timezone';

    private $format;
    private $timezone;

    private static $supportedTypes = array(
        \DateTimeInterface::class => true,
        \DateTimeImmutable::class => true,
        \DateTime::class => true,
    );

    public function __construct(?string $format = \DateTime::RFC3339, \DateTimeZone $timezone = null)
    {
        $this->format = $format;
        $this->timezone = $timezone;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function normalize($object, $format = null, array $context = array())
    {
        if (!$object instanceof \DateTimeInterface) {
            throw new InvalidArgumentException('The object must implement the "\DateTimeInterface".');
        }

        $format = isset($context[self::FORMAT_KEY]) ? $context[self::FORMAT_KEY] : $this->format;
        $timezone = $this->getTimezone($context);

        if (null !== $timezone) {
            $object = clone $object;
            $object = $object->setTimezone($timezone);
        }

        return $object->format($format);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof \DateTimeInterface;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotNormalizableValueException
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $dateTimeFormat = isset($context[self::FORMAT_KEY]) ? $context[self::FORMAT_KEY] : null;
        $timezone = $this->getTimezone($context);

        if ('' === $data || null === $data) {
            throw new NotNormalizableValueException('The data is either an empty string or null, you should pass a string that can be parsed with the passed format or a valid DateTime string.');
        }

        if (null !== $dateTimeFormat) {
            $object = \DateTime::class === $class ? \DateTime::createFromFormat($dateTimeFormat, $data, $timezone) : \DateTimeImmutable::createFromFormat($dateTimeFormat, $data, $timezone);

            if (false !== $object) {
                return $object;
            }

            $dateTimeErrors = \DateTime::class === $class ? \DateTime::getLastErrors() : \DateTimeImmutable::getLastErrors();

            throw new NotNormalizableValueException(sprintf(
                'Parsing datetime string "%s" using format "%s" resulted in %d errors:'."\n".'%s',
                $data,
                $dateTimeFormat,
                $dateTimeErrors['error_count'],
                implode("\n", $this->formatDateTimeErrors($dateTimeErrors['errors']))
            ));
        }

        try {
            return \DateTime::class === $class ? new \DateTime($data, $timezone) : new \DateTimeImmutable($data, $timezone);
        } catch (\Exception $e) {
            throw new NotNormalizableValueException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return isset(self::$supportedTypes[$type]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return __CLASS__ === \get_class($this);
    }

    /**
     * Formats datetime errors.
     *
     * @return string[]
     */
    private function formatDateTimeErrors(array $errors)
    {
        $formattedErrors = array();

        foreach ($errors as $pos => $message) {
            $formattedErrors[] = sprintf('at position %d: %s', $pos, $message);
        }

        return $formattedErrors;
    }

    private function getTimezone(array $context)
    {
        $dateTimeZone = array_key_exists(self::TIMEZONE_KEY, $context) ? $context[self::TIMEZONE_KEY] : $this->timezone;

        if (null === $dateTimeZone) {
            return null;
        }

        return $dateTimeZone instanceof \DateTimeZone ? $dateTimeZone : new \DateTimeZone($dateTimeZone);
    }
}
