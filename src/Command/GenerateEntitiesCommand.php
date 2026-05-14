<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:generate:entities',
    description: 'Advanced generator FIXED (PK + FK + NULL SAFE)'
)]
class GenerateEntitiesCommand extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sm = $this->connection->createSchemaManager();
        $tables = $sm->listTables();

        foreach ($tables as $table) {
            $tableName = $table->getName();
            $className = ucfirst($tableName);
            $filePath = "src/Entity/$className.php";

            if (file_exists($filePath)) {
                $output->writeln("Skipping $className");
                continue;
            }

            $output->writeln("Generating: $className");

            $content = "<?php\n\nnamespace App\Entity;\n\nuse Doctrine\ORM\Mapping as ORM;\n\n#[ORM\Entity]\nclass $className\n{\n";

            $primaryKeys = $table->getPrimaryKey()?->getColumns() ?? [];
            $foreignKeys = $table->getForeignKeys();

            // FK mapping
            $fkMap = [];
            foreach ($foreignKeys as $fk) {
                foreach ($fk->getLocalColumns() as $col) {
                    $fkMap[$col] = $fk->getForeignTableName();
                }
            }

            foreach ($table->getColumns() as $column) {
                $name = $column->getName();
                $dbType = strtolower($column->getColumnDefinition() ?? '');

                // PRIMARY KEY
                if (in_array($name, $primaryKeys)) {
                    $content .= "\n    #[ORM\Id]\n";

                    if ($column->getAutoincrement()) {
                        $content .= "    #[ORM\GeneratedValue]\n";
                    }

                    $content .= "    #[ORM\Column]\n";
                    $content .= "    private ?int \$$name = null;\n";
                    continue;
                }

                // FOREIGN KEY
                if (isset($fkMap[$name])) {
                    $target = ucfirst($fkMap[$name]);
                    $property = str_replace('_id', '', $name);

                    $content .= "\n    #[ORM\ManyToOne]\n";
                    $content .= "    #[ORM\JoinColumn(name: \"$name\", referencedColumnName: \"id\")]\n";
                    $content .= "    private ?$target \$$property = null;\n";
                    continue;
                }

                // TYPE DETECTION
                $phpType = "string";

                if (str_contains($dbType, 'int')) {
                    $phpType = "int";
                } elseif (str_contains($dbType, 'date')) {
                    $phpType = "\\DateTimeInterface";
                } elseif (str_contains($dbType, 'decimal') || str_contains($dbType, 'float')) {
                    $phpType = "float";
                } elseif (str_contains($dbType, 'tinyint(1)')) {
                    $phpType = "bool";
                }

                // ✅ FIX NULL PROBLEM
                $isNullable = !$column->getNotnull();
                $typePrefix = $isNullable ? '?' : '';
                $defaultValue = $isNullable ? ' = null' : '';

                $content .= "\n    #[ORM\Column(nullable: " . ($isNullable ? "true" : "false") . ")]\n";
                $content .= "    private {$typePrefix}{$phpType} \$$name{$defaultValue};\n";
            }

            $content .= "\n}\n";

            file_put_contents($filePath, $content);
        }

        return Command::SUCCESS;
    }
}