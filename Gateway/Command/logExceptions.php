<?php
namespace Veriteworks\Paypal\Gateway\Command;

trait logExceptions
{
    /**
     * @param Phrase[] $fails
     * @return void
     */
    private function logExceptions(array $fails)
    {
        foreach ($fails as $failPhrase) {
            $this->logger->critical((string) $failPhrase);
        }
    }
}
