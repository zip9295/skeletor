<?php

namespace Skeletor\Visitor\Model;

use Skeletor\Core\Model\Model;

class ForgotPasswordToken extends Model
{
    /**
     * @param $forgotPasswordTokenId
     * @param $user
     * @param $token
     * @param $requestedAt
     * @param $created_at
     * @param $updated_at
     */
    public function __construct(
        private $forgotPasswordTokenId,
        private $visitorId,
        private $token,
        private $requestedAt,
                $created_at,
                $updated_at
    )
    {
        parent::__construct($created_at, $updated_at);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return (int)$this->forgotPasswordTokenId;
    }

    /**
     * @return mixed
     */
    public function getVisitor()
    {
        return $this->visitor;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getRequestedAt()
    {
        return $this->requestedAt;
    }

}