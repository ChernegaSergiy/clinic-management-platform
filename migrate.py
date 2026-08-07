import os, re, subprocess, sys, textwrap

ROOT = "/home/serhii/Development/University/Вебтехнології/clinic-management-platform/www"

# module -> (permissions list[(code, desc)], role_mappings dict[role]->list[perm], policy (resource_key, PolicyClass) or None)
DATA = {
    "Admin": (
        [("admin.manage", "Керування адмінпанеллю")],
        {"admin": ["admin.manage"]},
        None,
    ),
    "Appointment": (
        [
            ("appointment.view.any", "Перегляд будь-якого запису"),
            ("appointment.view.own", "Перегляд власних записів"),
            ("appointment.edit.any", "Редагування будь-якого запису"),
            ("appointment.edit.own", "Редагування власних записів"),
            ("appointment.create", "Створення записів"),
        ],
        {
            "admin": ["appointment.view.any", "appointment.edit.any", "appointment.create"],
            "medical_manager": ["appointment.view.any", "appointment.edit.any", "appointment.create"],
            "registrar": ["appointment.view.any", "appointment.edit.any", "appointment.create"],
            "doctor": ["appointment.view.own", "appointment.edit.own", "appointment.create"],
            "nurse": ["appointment.view.own"],
            "billing": ["appointment.view.any"],
        },
        ("appointment", "AppointmentPolicy"),
    ),
    "Billing": (
        [("billing.read", "Перегляд рахунків"), ("billing.manage", "Керування рахунками")],
        {"admin": ["billing.read", "billing.manage"], "billing": ["billing.read", "billing.manage"]},
        ("billing", "BillingPolicy"),
    ),
    "ClinicalReference": (
        [("clinical.manage", "Керування клінічними довідниками")],
        {"admin": ["clinical.manage"], "medical_manager": ["clinical.manage"]},
        ("clinical", "ClinicalReferencePolicy"),
    ),
    "Dashboard": (
        [("dashboard.view", "Перегляд панелі"), ("dashboard.export", "Експорт даних")],
        {
            "admin": ["dashboard.view", "dashboard.export"],
            "medical_manager": ["dashboard.view", "dashboard.export"],
            "registrar": ["dashboard.view"],
            "doctor": ["dashboard.view"],
            "nurse": ["dashboard.view"],
            "lab_technician": ["dashboard.view"],
            "billing": ["dashboard.view", "dashboard.export"],
            "inventory_manager": ["dashboard.view"],
            "hr_manager": ["dashboard.view"],
        },
        ("dashboard", "DashboardPolicy"),
    ),
    "Department": (
        [
            ("department.read", "Перегляд відділень"),
            ("department.write", "Редагування відділень"),
            ("department.delete", "Видалення відділень"),
            ("department.manage", "Керування відділеннями"),
        ],
        {
            "admin": ["department.read", "department.write", "department.delete", "department.manage"],
            "medical_manager": ["department.read", "department.write"],
        },
        ("department", "DepartmentPolicy"),
    ),
    "Hrm": (
        [
            ("hrm.read", "Перегляд співробітників"),
            ("hrm.write", "Редагування співробітників"),
            ("hrm.manage", "Керування співробітниками"),
        ],
        {
            "admin": ["hrm.read", "hrm.write", "hrm.manage"],
            "hr_manager": ["hrm.read", "hrm.write", "hrm.manage"],
            "medical_manager": ["hrm.read"],
        },
        ("hrm", "HrmPolicy"),
    ),
    "Insurance": (
        [("insurance.manage", "Керування страховкою")],
        {"admin": ["insurance.manage"]},
        ("insurance", "InsurancePolicy"),
    ),
    "Inventory": (
        [("inventory.manage", "Керування складом")],
        {"admin": ["inventory.manage"], "inventory_manager": ["inventory.manage"]},
        ("inventory", "InventoryPolicy"),
    ),
    "Kpi": (
        [("kpi.read", "Перегляд KPI"), ("kpi.manage", "Керування KPI")],
        {"admin": ["kpi.read", "kpi.manage"], "medical_manager": ["kpi.read"]},
        ("kpi", "KpiPolicy"),
    ),
    "LabOrder": (
        [
            ("lab_order.view.any", "Перегляд будь-якого лабораторного дослідження"),
            ("lab_order.view.own", "Перегляд власних лабораторних досліджень"),
            ("lab_order.edit.any", "Редагування будь-якого лабораторного дослідження"),
            ("lab_order.edit.own", "Редагування власних лабораторних досліджень"),
            ("lab_order.create", "Створення лабораторних досліджень"),
        ],
        {
            "admin": ["lab_order.view.any", "lab_order.edit.any", "lab_order.create"],
            "medical_manager": ["lab_order.view.any"],
            "lab_technician": ["lab_order.view.any", "lab_order.edit.any", "lab_order.create"],
            "doctor": ["lab_order.view.own", "lab_order.edit.own", "lab_order.create"],
            "nurse": ["lab_order.view.own"],
        },
        ("lab_order", "LabOrderPolicy"),
    ),
    "MedicalRecord": (
        [
            ("medical_record.view.any", "Перегляд будь-якого медичного запису"),
            ("medical_record.view.own", "Перегляд власних медичних записів"),
            ("medical_record.edit.own", "Редагування власних медичних записів"),
            ("medical_record.edit.any", "Редагування будь-яких медичних записів"),
            ("medical_record.create", "Створення медичних записів"),
        ],
        {
            "admin": ["medical_record.view.any", "medical_record.edit.any", "medical_record.create"],
            "medical_manager": ["medical_record.view.any", "medical_record.edit.any"],
            "doctor": ["medical_record.view.own", "medical_record.edit.own", "medical_record.create"],
            "nurse": ["medical_record.view.own"],
        },
        ("medical_record", "MedicalRecordPolicy"),
    ),
    "News": (
        [("news.read", "Перегляд новин"), ("news.manage", "Керування новинами")],
        {"admin": ["news.read", "news.manage"]},
        ("news", "NewsPolicy"),
    ),
    "Notification": (
        [("notifications.read", "Перегляд сповіщень")],
        {
            "admin": ["notifications.read"],
            "medical_manager": ["notifications.read"],
            "registrar": ["notifications.read"],
            "doctor": ["notifications.read"],
            "nurse": ["notifications.read"],
            "lab_technician": ["notifications.read"],
            "billing": ["notifications.read"],
            "inventory_manager": ["notifications.read"],
            "hr_manager": ["notifications.read"],
        },
        None,
    ),
    "Patient": (
        [
            ("patient.view.any", "Перегляд будь-якого пацієнта"),
            ("patient.view.own", "Перегляд призначених пацієнтів"),
            ("patient.edit.any", "Редагування будь-якого пацієнта"),
            ("patient.edit.own", "Редагування призначених пацієнтів"),
            ("patient.create", "Створення пацієнтів"),
        ],
        {
            "admin": ["patient.view.any", "patient.edit.any", "patient.create"],
            "medical_manager": ["patient.view.any"],
            "registrar": ["patient.view.any", "patient.edit.any", "patient.create"],
            "doctor": ["patient.view.own", "patient.edit.own"],
            "nurse": ["patient.view.own"],
        },
        ("patient", "PatientPolicy"),
    ),
    "Prescription": (
        [
            ("prescription.view.any", "Перегляд будь-якого рецепту"),
            ("prescription.view.own", "Перегляд власних рецептів"),
            ("prescription.edit.own", "Редагування власних рецептів"),
            ("prescription.edit.any", "Редагування будь-яких рецептів"),
            ("prescription.create.own", "Створення власних рецептів"),
            ("prescription.create.any", "Створення рецептів від імені будь-якого лікаря"),
        ],
        {
            "admin": ["prescription.view.any", "prescription.edit.any", "prescription.create.any"],
            "medical_manager": ["prescription.view.any", "prescription.edit.any", "prescription.create.any"],
            "doctor": ["prescription.view.own", "prescription.edit.own", "prescription.create.own"],
            "nurse": ["prescription.view.own"],
        },
        ("prescription", "PrescriptionPolicy"),
    ),
    "Room": (
        [("rooms.manage", "Керування приміщеннями")],
        {"admin": ["rooms.manage"]},
        ("rooms", "RoomPolicy"),
    ),
    "Schedule": (
        [
            ("schedules.manage_all", "Керування всіма розкладами"),
            ("schedules.manage_own", "Керування власним розкладом"),
        ],
        {
            "admin": ["schedules.manage_all"],
            "medical_manager": ["schedules.manage_all"],
            "doctor": ["schedules.manage_own"],
        },
        ("schedules", "SchedulePolicy"),
    ),
    "Site": ([], {}, None),
    "User": ([], {}, None),
}

# modules that had real feature flags in module.yaml (config/reference values were all true by default)
FEATURES = {
    "Patient": ["insurance", "policies", "export"],
    "User": ["oauth", "profile_photo"],
    "Appointment": ["waitlist", "api"],
    "Dashboard": ["export"],
}

ORDER = [
    "Admin", "Appointment", "Billing", "ClinicalReference", "Dashboard", "Department",
    "Hrm", "Insurance", "Inventory", "Kpi", "LabOrder", "MedicalRecord", "News",
    "Notification", "Patient", "Prescription", "Room", "Schedule", "Site", "User",
]

def run(cmd, **kw):
    r = subprocess.run(cmd, shell=True, cwd=ROOT, capture_output=True, text=True, **kw)
    if r.returncode != 0:
        print("CMD FAILED:", cmd)
        print(r.stdout)
        print(r.stderr)
        sys.exit(1)
    return r.stdout

def sed_tree_replace(old, new):
    """Replace literal string old->new across all php/yaml/twig files in src/ and tests/, excluding vendor."""
    for base in ("src", "tests"):
        for dirpath, dirnames, filenames in os.walk(os.path.join(ROOT, base)):
            if "vendor" in dirpath.split(os.sep):
                continue
            for fn in filenames:
                if not (fn.endswith(".php") or fn.endswith(".yaml") or fn.endswith(".twig")):
                    continue
                fp = os.path.join(dirpath, fn)
                with open(fp, "r", encoding="utf-8", errors="ignore") as f:
                    content = f.read()
                if old in content:
                    content = content.replace(old, new)
                    with open(fp, "w", encoding="utf-8") as f:
                        f.write(content)

def build_extension_php(mod):
    ns = f"App\\Bundles\\{mod}Bundle\\DependencyInjection"
    lines = []
    lines.append("<?php")
    lines.append("")
    lines.append(f"namespace {ns};")
    lines.append("")
    lines.append("use Symfony\\Component\\DependencyInjection\\ContainerBuilder;")
    lines.append("use Symfony\\Component\\DependencyInjection\\Extension\\Extension;")
    lines.append("")
    lines.append(f"class {mod}Extension extends Extension")
    lines.append("{")
    lines.append("    public function load(array $configs, ContainerBuilder $container) : void")
    lines.append("    {")
    if mod in FEATURES:
        lines.append("        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);")
        lines.append("")
        for feat in FEATURES[mod]:
            lines.append(f"        $container->setParameter('{mod.lower()}.features.{feat}', $config['features']['{feat}']);")
    lines.append("    }")
    lines.append("}")
    lines.append("")
    return "\n".join(lines)

def build_compiler_pass_php(mod):
    perms, roles, policy = DATA[mod]
    ns = f"App\\Bundles\\{mod}Bundle\\DependencyInjection\\Compiler"
    lines = []
    lines.append("<?php")
    lines.append("")
    lines.append(f"namespace {ns};")
    lines.append("")
    if policy:
        lines.append(f"use App\\Bundles\\{mod}Bundle\\{policy[1]};")
    lines.append("use App\\Core\\Auth\\PermissionRegistry;")
    lines.append("use App\\Core\\Auth\\PolicyRegistry;")
    lines.append("use Symfony\\Component\\DependencyInjection\\Compiler\\CompilerPassInterface;")
    lines.append("use Symfony\\Component\\DependencyInjection\\ContainerBuilder;")
    lines.append("")
    lines.append(f"class {mod}PermissionsPass implements CompilerPassInterface")
    lines.append("{")
    lines.append("    public function process(ContainerBuilder $container) : void")
    lines.append("    {")
    if perms or roles:
        lines.append("        if ($container->hasDefinition(PermissionRegistry::class)) {")
        lines.append("            $registry = $container->getDefinition(PermissionRegistry::class);")
        for (code, desc) in perms:
            lines.append(f"            $registry->addMethodCall('add', ['{code}', '{desc}']);")
        if perms and roles:
            lines.append("")
        for r, plist in roles.items():
            plist_str = ", ".join(f"'{p}'" for p in plist)
            lines.append(f"            $registry->addMethodCall('addRoleMapping', ['{r}', [{plist_str}]]);")
        lines.append("        }")
        
    if policy:
        lines.append("")
        lines.append("        if ($container->hasDefinition(PolicyRegistry::class)) {")
        lines.append("            $registry = $container->getDefinition(PolicyRegistry::class);")
        lines.append(f"            $registry->addMethodCall('register', ['{policy[0]}', {policy[1]}::class]);")
        lines.append("        }")
    lines.append("    }")
    lines.append("}")
    lines.append("")
    return "\n".join(lines)

def build_configuration_php(mod):
    ns = f"App\\Bundles\\{mod}Bundle\\DependencyInjection"
    feats = FEATURES.get(mod, [])
    lines = []
    lines.append("<?php")
    lines.append("")
    lines.append(f"namespace {ns};")
    lines.append("")
    lines.append("use Symfony\\Component\\Config\\Definition\\Builder\\TreeBuilder;")
    lines.append("use Symfony\\Component\\Config\\Definition\\ConfigurationInterface;")
    lines.append("")
    lines.append("class Configuration implements ConfigurationInterface")
    lines.append("{")
    lines.append("    public function getConfigTreeBuilder() : TreeBuilder")
    lines.append("    {")
    lines.append(f"        $treeBuilder = new TreeBuilder('{mod.lower()}');")
    lines.append("        $rootNode = $treeBuilder->getRootNode();")
    lines.append("")
    lines.append("        $rootNode")
    lines.append("            ->children()")
    lines.append("                ->arrayNode('features')")
    lines.append("                    ->addDefaultsIfNotSet()")
    lines.append("                    ->children()")
    for feat in feats:
        lines.append(f"                        ->booleanNode('{feat}')->defaultTrue()->end()")
    lines.append("                    ->end()")
    lines.append("                ->end()")
    lines.append("            ->end();")
    lines.append("")
    lines.append("        return $treeBuilder;")
    lines.append("    }")
    lines.append("}")
    lines.append("")
    return "\n".join(lines)

def build_bundle_php(mod):
    perms, roles, policy = DATA[mod]
    lines = []
    lines.append("<?php")
    lines.append("")
    lines.append(f"namespace App\\Bundles\\{mod}Bundle;")
    lines.append("")
    if perms or roles or policy:
        lines.append(f"use App\\Bundles\\{mod}Bundle\\DependencyInjection\\Compiler\\{mod}PermissionsPass;")
        lines.append("use Symfony\\Component\\DependencyInjection\\ContainerBuilder;")
    lines.append("use Symfony\\Component\\HttpKernel\\Bundle\\Bundle;")
    lines.append("")
    lines.append(f"class {mod}Bundle extends Bundle")
    lines.append("{")
    if perms or roles or policy:
        lines.append("    public function build(ContainerBuilder $container) : void")
        lines.append("    {")
        lines.append("        parent::build($container);")
        lines.append(f"        $container->addCompilerPass(new {mod}PermissionsPass());")
        lines.append("    }")
    lines.append("}")
    lines.append("")
    return "\n".join(lines)

def migrate(mod):
    old_dir = f"src/Module/{mod}"
    new_dir = f"src/Bundles/{mod}Bundle"

    if not os.path.isdir(os.path.join(ROOT, old_dir)):
        print(f"SKIP {mod}: {old_dir} not found")
        return

    # Instead of git mv and global namespace replacement, the script ONLY generates the bundle scaffold.
    # Copying files and changing namespaces will be done manually/analytically for each module.
    
    os.makedirs(os.path.join(ROOT, new_dir), exist_ok=True)

    # 3. create DependencyInjection dir + Extension (+ Configuration) + Bundle.php
    di_dir = os.path.join(ROOT, new_dir, "DependencyInjection")
    os.makedirs(di_dir, exist_ok=True)
    with open(os.path.join(di_dir, f"{mod}Extension.php"), "w") as f:
        f.write(build_extension_php(mod))
    if mod in FEATURES:
        with open(os.path.join(di_dir, "Configuration.php"), "w") as f:
            f.write(build_configuration_php(mod))
            
    perms, roles, policy = DATA[mod]
    if perms or roles or policy:
        comp_dir = os.path.join(di_dir, "Compiler")
        os.makedirs(comp_dir, exist_ok=True)
        with open(os.path.join(comp_dir, f"{mod}PermissionsPass.php"), "w") as f:
            f.write(build_compiler_pass_php(mod))
            
    with open(os.path.join(ROOT, new_dir, f"{mod}Bundle.php"), "w") as f:
        f.write(build_bundle_php(mod))

    # Run php-cs-fixer for newly created files
    print("🧹 Running php-cs-fixer...")
    subprocess.run(f"vendor/bin/php-cs-fixer fix {new_dir}", shell=True, cwd=ROOT, capture_output=True)

    # 4. register bundle in config/bundles.php
    bundles_path = os.path.join(ROOT, "config/bundles.php")
    with open(bundles_path) as f:
        content = f.read()
    marker = "];"
    insertion = f"    App\\Bundles\\{mod}Bundle\\{mod}Bundle::class => ['all' => true],\n{marker}"
    
    if f"App\\Bundles\\{mod}Bundle\\{mod}Bundle::class" not in content:
        assert content.rstrip().endswith(marker), f"unexpected bundles.php format for {mod}"
        content = content.rstrip()[: -len(marker)] + insertion + "\n"
        with open(bundles_path, "w") as f:
            f.write(content)

    print(f"✅ Successfully generated bundle scaffold for {mod} in {new_dir}")
    print(f"⚠️ The {old_dir} directory is NOT deleted and namespaces are NOT changed (this is done manually).")


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python3 migrate.py <ModuleName>")
        print(f"Available modules: {', '.join(ORDER)}")
        sys.exit(1)

    target_mod = sys.argv[1]
    if target_mod not in DATA:
        print(f"Error: Module '{target_mod}' not found in configuration.")
        sys.exit(1)

    migrate(target_mod)
