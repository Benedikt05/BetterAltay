<?php declare(strict_types = 1);

namespace DaveRandom\CallbackValidator\Test;

use DaveRandom\CallbackValidator\BuiltInTypes;
use DaveRandom\CallbackValidator\Type;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    private function createTypeInstance($type, $flags, $allowsCovariance, $allowsContravariance): Type
    {
        return new class($type, $flags, $allowsCovariance, $allowsContravariance) extends Type {
            public function __construct($type, $flags, $allowsCovariance, $allowsContravariance) {
                parent::__construct($type, $flags, $allowsCovariance, $allowsContravariance);
            }
        };
    }

    public function testNullType()
    {
        $type = $this->createTypeInstance(null, 0, false, false);

        $this->assertSame(null, $type->typeName);
        $this->assertFalse($type->isNullable);
        $this->assertFalse($type->isByReference);
        $this->assertFalse($type->isWeak);
        $this->assertFalse($type->allowsCovariance);
        $this->assertFalse($type->allowsContravariance);
    }

    public function testNullableFlag()
    {
        $type = $this->createTypeInstance(null, Type::NULLABLE, false, false);

        $this->assertSame(null, $type->typeName);
        $this->assertTrue($type->isNullable);
        $this->assertFalse($type->isByReference);
        $this->assertFalse($type->isWeak);
        $this->assertFalse($type->allowsCovariance);
        $this->assertFalse($type->allowsContravariance);
    }

    public function testReferenceFlag()
    {
        $type = $this->createTypeInstance(null, Type::REFERENCE, false, false);

        $this->assertSame(null, $type->typeName);
        $this->assertFalse($type->isNullable);
        $this->assertTrue($type->isByReference);
        $this->assertFalse($type->isWeak);
        $this->assertFalse($type->allowsCovariance);
        $this->assertFalse($type->allowsContravariance);
    }

    public function testWeakFlag()
    {
        $type = $this->createTypeInstance(null, Type::WEAK, false, false);

        $this->assertSame(null, $type->typeName);
        $this->assertFalse($type->isNullable);
        $this->assertFalse($type->isByReference);
        $this->assertTrue($type->isWeak);
        $this->assertFalse($type->allowsCovariance);
        $this->assertFalse($type->allowsContravariance);
    }

    public function testMultipleFlags()
    {
        $type = $this->createTypeInstance(null, Type::NULLABLE | Type::REFERENCE | Type::WEAK, false, false);

        $this->assertSame(null, $type->typeName);
        $this->assertTrue($type->isNullable);
        $this->assertTrue($type->isByReference);
        $this->assertTrue($type->isWeak);
        $this->assertFalse($type->allowsCovariance);
        $this->assertFalse($type->allowsContravariance);
    }

    public function testAllowsCovarianceArg()
    {
        $type = $this->createTypeInstance(null, 0, true, false);

        $this->assertSame(null, $type->typeName);
        $this->assertFalse($type->isNullable);
        $this->assertFalse($type->isByReference);
        $this->assertFalse($type->isWeak);
        $this->assertTrue($type->allowsCovariance);
        $this->assertFalse($type->allowsContravariance);
    }

    public function testAllowsContravarianceArg()
    {
        $type = $this->createTypeInstance(null, 0, false, true);

        $this->assertSame(null, $type->typeName);
        $this->assertFalse($type->isNullable);
        $this->assertFalse($type->isByReference);
        $this->assertFalse($type->isWeak);
        $this->assertFalse($type->allowsCovariance);
        $this->assertTrue($type->allowsContravariance);
    }

    public function testStringTypeName()
    {
        $type = $this->createTypeInstance(Type::class, 0, false, false);

        $this->assertSame(Type::class, $type->typeName);
        $this->assertFalse($type->isNullable);
        $this->assertFalse($type->isByReference);
        $this->assertFalse($type->isWeak);
        $this->assertFalse($type->allowsCovariance);
        $this->assertFalse($type->allowsContravariance);
    }

    public function testNonStringTypeName()
    {
        $type = $this->createTypeInstance(1, 0, false, false);

        $this->assertSame('1', $type->typeName);
        $this->assertFalse($type->isNullable);
        $this->assertFalse($type->isByReference);
        $this->assertFalse($type->isWeak);
        $this->assertFalse($type->allowsCovariance);
        $this->assertFalse($type->allowsContravariance);
    }

    public function testByRefMustBeIdentical()
    {
        $type = $this->createTypeInstance(Type::class, 0, false, false);

        $this->assertSame(Type::class, $type->typeName);
        $this->assertFalse($type->isByReference);

        $this->assertTrue($type->isSatisfiedBy(Type::class, false, false));
        $this->assertFalse($type->isSatisfiedBy(Type::class, false, true));

        $type = $this->createTypeInstance(Type::class, Type::REFERENCE, false, false);

        $this->assertSame(Type::class, $type->typeName);
        $this->assertTrue($type->isByReference);

        $this->assertTrue($type->isSatisfiedBy(Type::class, false, true));
        $this->assertFalse($type->isSatisfiedBy(Type::class, false, false));
    }

    public function testIdenticalTypesAlwaysMatch()
    {
        $type = $this->createTypeInstance(Type::class, 0, false, false);

        $this->assertSame(Type::class, $type->typeName);
        $this->assertFalse($type->isNullable);

        $this->assertTrue($type->isSatisfiedBy(Type::class, false, false));

        $type = $this->createTypeInstance(Type::class, Type::NULLABLE, false, false);

        $this->assertSame(Type::class, $type->typeName);
        $this->assertTrue($type->isNullable);

        $this->assertTrue($type->isSatisfiedBy(Type::class, true, false));
    }

    public function testValidCovariantTypeMatches()
    {
        $type = $this->createTypeInstance(Type::class, Type::NULLABLE, true, false);

        $this->assertSame(Type::class, $type->typeName);
        $this->assertTrue($type->isNullable);

        $this->assertTrue($type->isSatisfiedBy(Type::class, false, false));
    }

    public function testInvalidCovariantTypeDoesNotMatch()
    {
        $type = $this->createTypeInstance(Type::class, 0, true, false);

        $this->assertSame(Type::class, $type->typeName);
        $this->assertFalse($type->isNullable);

        $this->assertFalse($type->isSatisfiedBy(Type::class, true, false));
    }

    public function testValidCovariantTypeMatchesDoesNotMatchWhenCovarianceIsNotAllowed()
    {
        $type = $this->createTypeInstance(Type::class, Type::NULLABLE, false, false);

        $this->assertSame(Type::class, $type->typeName);
        $this->assertTrue($type->isNullable);

        $this->assertFalse($type->isSatisfiedBy(Type::class, false, false));
    }

    public function testCovarianceRespectsWeakMode()
    {
        $type = $this->createTypeInstance(BuiltInTypes::STRING, 0, true, false);

        $this->assertSame(BuiltInTypes::STRING, $type->typeName);
        $this->assertFalse($type->isWeak);

        $this->assertFalse($type->isSatisfiedBy(BuiltInTypes::INT, false, false));

        $type = $this->createTypeInstance(BuiltInTypes::STRING, Type::WEAK, true, false);

        $this->assertSame(BuiltInTypes::STRING, $type->typeName);
        $this->assertTrue($type->isWeak);

        $this->assertTrue($type->isSatisfiedBy(BuiltInTypes::INT, false, false));
    }

    public function testValidContravariantTypeMatches()
    {
        $type = $this->createTypeInstance(Type::class, 0, false, true);

        $this->assertSame(Type::class, $type->typeName);
        $this->assertFalse($type->isNullable);
        $this->assertTrue($type->isSatisfiedBy(Type::class, true, false));
    }

    public function testInvalidContravariantTypeDoesNotMatch()
    {
        $type = $this->createTypeInstance(Type::class, Type::NULLABLE, false, true);

        $this->assertSame(Type::class, $type->typeName);
        $this->assertTrue($type->isNullable);

        $this->assertFalse($type->isSatisfiedBy(Type::class, false, false));
    }

    public function testValidContravariantTypeDoesNotMatchWhenContravarianceIsNotAllowed()
    {
        $type = $this->createTypeInstance(Type::class, 0, false, false);

        $this->assertSame(Type::class, $type->typeName);
        $this->assertFalse($type->isNullable);

        $this->assertFalse($type->isSatisfiedBy(Type::class, true, false));
    }

    public function testContravarianceRespectsWeakMode()
    {
        $type = $this->createTypeInstance(BuiltInTypes::STRING, 0, false, true);

        $this->assertSame(BuiltInTypes::STRING, $type->typeName);
        $this->assertFalse($type->isWeak);

        $this->assertFalse($type->isSatisfiedBy(BuiltInTypes::INT, false, false));

        $type = $this->createTypeInstance(BuiltInTypes::STRING, Type::WEAK, false, true);

        $this->assertSame(BuiltInTypes::STRING, $type->typeName);
        $this->assertTrue($type->isWeak);

        $this->assertTrue($type->isSatisfiedBy(BuiltInTypes::INT, false, false));
    }

    public function testInvarianceRespectsWeakMode()
    {
        $type = $this->createTypeInstance(BuiltInTypes::STRING, 0, false, false);

        $this->assertSame(BuiltInTypes::STRING, $type->typeName);
        $this->assertFalse($type->isWeak);

        $this->assertFalse($type->isSatisfiedBy(BuiltInTypes::INT, false, false));

        $type = $this->createTypeInstance(BuiltInTypes::STRING, Type::WEAK, false, false);

        $this->assertSame(BuiltInTypes::STRING, $type->typeName);
        $this->assertTrue($type->isWeak);

        $this->assertTrue($type->isSatisfiedBy(BuiltInTypes::INT, false, false));

        $type = $this->createTypeInstance(BuiltInTypes::STRING, Type::WEAK | Type::NULLABLE, false, false);

        $this->assertSame(BuiltInTypes::STRING, $type->typeName);
        $this->assertTrue($type->isWeak);

        $this->assertFalse($type->isSatisfiedBy(BuiltInTypes::INT, false, false));
    }
}
