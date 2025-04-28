<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

use App\Models\PreseedConfig;

class PreseedController extends Controller
{
    // Bestaande index methode (voorbeeld)
    public function index()
    {
        // Logica om de pagina met het formulier te tonen
        return Inertia::render('Preseed/Index'); // Pas aan naar je component naam
    }

    /**
     * Genereer een preview van de preseed inhoud.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function preview(Request $request): JsonResponse
    {
        try {
            // 1. Valideer de inkomende data (GEBRUIK DEZELFDE REGELS ALS store!)
            //    Het is cruciaal om hier te valideren om errors in generatePreseedContent te voorkomen
            //    en om geen preview te genereren van ongeldige data.
            $validatedData = $request->validate([
                // --- KOPIEER HIER ALLE VALIDATIEREGELS UIT DE store() METHODE ---
                'config_name' => 'required|string|max:255', // Validatie is nog steeds nuttig
                'language' => 'required|string|max:10',
                'country' => 'required|string|max:10',
                'locale' => 'required|string|max:20',
                'keyboard_layout' => 'required|string|max:10',
                'hostname' => 'required|string|max:63',
                'domain' => 'nullable|string|max:255',
                'network_method' => 'required|in:dhcp,static',
                'static_ip' => 'required_if:network_method,static|nullable|ip',
                'static_netmask' => 'required_if:network_method,static|nullable|ip',
                'static_gateway' => 'required_if:network_method,static|nullable|ip',
                'static_dns' => 'required_if:network_method,static|nullable|string',
                'network_device' => 'nullable|string|max:20',
                'mirror_protocol' => 'required|in:http,ftp',
                'mirror_hostname' => 'required|string|max:255',
                'mirror_directory' => 'required|string|max:255',
                'mirror_proxy' => 'nullable|url',
                // Belangrijk: Valideer wachtwoorden niet als 'confirmed' voor preview,
                // tenzij je de _confirmation velden ook meestuurt.
                'root_password' => ['nullable', /*'confirmed',*/ 'min:8'],
                'disable_root_login' => 'required|boolean',
                'create_user' => 'required|boolean',
                'username' => 'required_if:create_user,true|nullable|string|max:32',
                'user_fullname' => 'required_if:create_user,true|nullable|string|max:255',
                'user_password' => ['required_if:create_user,true', 'nullable', /*'confirmed',*/ 'min:8'],
                'partitioning_method' => 'required|in:regular,lvm,crypto,cis_auto', // Voeg evt 'cis_auto' toe
                'separate_home' => 'required|boolean',
                'tasks' => 'required|array|min:1',
                'tasks.*' => 'string|in:standard,desktop,gnome-desktop,kde-desktop,xfce-desktop,lxde-desktop,lxqt-desktop,mate-desktop,cinnamon-desktop,web-server,print-server,ssh-server',
                'additional_packages' => 'nullable|string',
                'timezone' => 'required|string|max:50',
                'grub_install_device' => 'required|string|max:255',
                'late_command' => 'nullable|string',
                'cis_compliant' => 'required|boolean',
                // -----------------------------------------------------------------
            ]);

            // 2. Genereer de preseed inhoud met de bestaande helper methode
            $preseedContent = $this->generatePreseedContent($validatedData);

            // 3. Geef de inhoud terug als JSON
            return response()->json([
                'success' => true,
                'previewContent' => $preseedContent,
            ]);

        } catch (ValidationException $e) {
            // Als validatie faalt, geef de errors terug als JSON
            return response()->json([
                'success' => false,
                'message' => 'Validatie mislukt.',
                'errors' => $e->errors(),
            ], 422); // HTTP status 422 Unprocessable Entity
        } catch (\Exception $e) {
            // Vang andere onverwachte fouten op
            Log::error("Fout bij genereren preseed preview: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Kon de preview niet genereren: ' . $e->getMessage(),
            ], 500); // HTTP status 500 Internal Server Error
        }
    }

    
    /**
     * Sla de data van het preseed formulier op.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validatedData = []; // Initialiseer om scope probleem in catch te voorkomen
        try {
            // Valideer data (met 'confirmed' voor wachtwoorden, zonder 'unique' op config_name)
            $validatedData = $request->validate([
                'config_name' => 'required|string|max:255',
                'language' => 'required|string|max:10',
                'country' => 'required|string|max:10',
                'locale' => 'required|string|max:20',
                'keyboard_layout' => 'required|string|max:10',
                'hostname' => 'required|string|max:63|regex:/^[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?$/',
                'domain' => ['nullable','string','max:255', 'regex:/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/'],
                'network_method' => 'required|in:dhcp,static',
                'static_ip' => 'required_if:network_method,static|nullable|ip',
                'static_netmask' => 'required_if:network_method,static|nullable|ip',
                'static_gateway' => 'required_if:network_method,static|nullable|ip',
                'static_dns' => 'required_if:network_method,static|nullable|string',
                'network_device' => 'nullable|string|max:20',
                'mirror_protocol' => 'required|in:http,https,ftp',
                'mirror_hostname' => 'required|string|max:255',
                'mirror_directory' => 'required|string|max:255',
                'mirror_proxy' => 'nullable|url',
                'root_password' => ['exclude_if:cis_compliant,true', 'nullable', 'confirmed', 'min:8'],
                'disable_root_login' => 'required|boolean',
                'create_user' => 'required|boolean',
                'username' => 'required_if:create_user,true|nullable|string|max:32|regex:/^[a-z_][a-z0-9_-]*$/',
                'user_fullname' => 'required_if:create_user,true|nullable|string|max:255',
                'user_password' => ['required_if:create_user,true', 'nullable', 'confirmed', 'min:8'],
                'partitioning_method' => ['exclude_if:cis_compliant,true', 'required_without:cis_compliant', 'nullable', 'in:regular,lvm,crypto'],
                'separate_home' => ['exclude_if:cis_compliant,true', 'required_without:cis_compliant', 'boolean'], // boolean ipv required
                'tasks' => 'required|array|min:1',
                'tasks.*' => 'string|in:standard,web-server,ssh-server',
                'additional_packages' => 'nullable|string',
                'timezone' => 'required|string|max:50',
                'grub_install_device' => 'required|string|max:255',
                'late_command' => 'nullable|string',
                'cis_compliant' => 'required|boolean',
            ]);

            // Genereer de preseed inhoud
            $preseedContent = $this->generatePreseedContent($validatedData);

            // Genereer een unieke hash ID
            do {
                $newHashId = Str::random(12);
            } while (PreseedConfig::where('hash_id', $newHashId)->exists());

            // Sla op in de database
            $config = PreseedConfig::create([
                'hash_id' => $newHashId,
                'original_name' => $validatedData['config_name'],
                'content' => $preseedContent,
                // 'user_id' => auth()->id(), // Als je gebruikers hebt
            ]);

            Log::info("Preseed configuratie '{$validatedData['config_name']}' (Hash: {$newHashId}) succesvol opgeslagen.");

            // --- AANPASSING HIER ---
            // Genereer de URL naar de show pagina
            $showUrl = route('preseed.show', ['waarde' => $newHashId]);

            // Stuur terug met een algemeen succesbericht en de URL apart
            return redirect()->route('preseed.index')
                      ->with('success', "Preseed configuratie '{$validatedData['config_name']}' succesvol aangemaakt!") // Algemeen bericht
                      ->with('newPreseedUrl', $showUrl); // Stuur de URL apart mee
            // --- EINDE AANPASSING ---

        } catch (ValidationException $e) {
             // Log de validatiefouten voor debugging indien nodig
             Log::warning("Validatiefout bij opslaan preseed: ", $e->errors());
             return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            // Gebruik $validatedData['config_name'] alleen als het bestaat
            $configNameForLog = isset($validatedData['config_name']) ? $validatedData['config_name'] : '[onbekend]';
            Log::error("Fout bij opslaan preseed '{$configNameForLog}': " . $e->getMessage(), ['exception' => $e]);
             return redirect()->back()->withInput()->with('error', 'Er is een onverwachte fout opgetreden bij het opslaan: ' . $e->getMessage());
        }
    }

    /**
     * Toon de preseed inhoud gebaseerd op de waarde.
     *
     * @param  string  $waarde
     * @return \Inertia\Response
     */
    public function show(string $waarde)
    {
        try {
            // Zoek de configuratie op basis van de hash_id
            $config = PreseedConfig::where('hash_id', $waarde)->firstOrFail();

            // Geef de inhoud terug als platte tekst
            return response($config->content, 200)
                      ->header('Content-Type', 'text/plain; charset=UTF-8')
                      // Optioneel: stel een bestandsnaam voor bij downloaden
                      ->header('Content-Disposition', 'inline; filename="' . Str::slug($config->original_name ?: $waarde) . '.cfg"');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Config niet gevonden, geef 404
            abort(404, "Preseed configuratie niet gevonden.");
        } catch (\Exception $e) {
            Log::error("Fout bij ophalen preseed (Hash: {$waarde}): " . $e->getMessage());
            abort(500, "Kon de preseed configuratie niet ophalen.");
        }
    }

    // ========================================================================
    // ==                       HELPER METHODES                            ==
    // ========================================================================

    /**
     * Genereer de inhoud van het preseed bestand.
     *
     * @param array $data Gevalideerde formulier data
     * @return string De preseed inhoud
     */
    private function generatePreseedContent(array $data): string
    {
        $output = [];
        $configName = $data['config_name'] ?? 'unnamed-config';

        // --- Header ---
        $output[] = "# =======================================================";
        $output[] = "# Debian Preseed Configuration";
        $output[] = "# Generated by: Preseed Generator"; // Pas aan naar je app naam
        $output[] = "# Config Name: " . $configName;
        $output[] = "# Generated At: " . now()->toIso8601String();
        if ($data['cis_compliant']) {
            $output[] = "# CIS Hardening: ENABLED (Basic)";
        }
        $output[] = "# =======================================================";
        $output[] = "";

        // --- Lokalisatie ---
        $output[] = "# --- Localization ---";
        $output[] = "d-i debian-installer/language string {$data['language']}";
        $output[] = "d-i debian-installer/country string {$data['country']}";
        $output[] = "d-i debian-installer/locale string {$data['locale']}";
        $output[] = "d-i console-setup/ask_detect boolean false"; // Voorkom vraag over toetsenbord detectie
        $output[] = "d-i keyboard-configuration/xkb-keymap select {$data['keyboard_layout']}";
        $output[] = "";

        // --- Netwerk ---
        $output[] = "# --- Network Configuration ---";
        $output[] = "d-i netcfg/get_hostname string {$data['hostname']}";
        $output[] = "d-i netcfg/get_domain string " . (!empty($data['domain']) ? $data['domain'] : 'localdomain');
        $output[] = "d-i netcfg/choose_interface select " . (!empty($data['network_device']) ? $data['network_device'] : 'auto');
        $output[] = "d-i netcfg/wireless_wep string"; // Leegmaken voor bedraad/geen WEP

        if ($data['network_method'] === 'static') {
            $output[] = "# Static IP Configuration";
            $output[] = "d-i netcfg/disable_dhcp boolean true";
            $output[] = "d-i netcfg/confirm_static boolean true";
            $output[] = "d-i netcfg/get_ipaddress string {$data['static_ip']}";
            $output[] = "d-i netcfg/get_netmask string {$data['static_netmask']}";
            $output[] = "d-i netcfg/get_gateway string {$data['static_gateway']}";
            $output[] = "d-i netcfg/get_nameservers string {$data['static_dns']}";
        } else {
            $output[] = "# DHCP Configuration";
            $output[] = "d-i netcfg/disable_dhcp boolean false";
            // Vraag niet om hostname via DHCP als we die al opgeven
            $output[] = "d-i netcfg/dhcp_hostname string";
            $output[] = "d-i netcfg/dhcp_domain string";
        }
        $output[] = "";

        // --- Mirror ---
        $output[] = "# --- Mirror Configuration ---";
        $output[] = "d-i mirror/country string manual";
        $output[] = "d-i mirror/protocol string {$data['mirror_protocol']}"; // http, https, ftp
        $mirrorHost = $data['mirror_hostname'];
        $mirrorDir = $data['mirror_directory'];
        $output[] = "d-i mirror/{$data['mirror_protocol']}/hostname string {$mirrorHost}";
        $output[] = "d-i mirror/{$data['mirror_protocol']}/directory string {$mirrorDir}";
        // Zorg ervoor dat de juiste suite wordt gebruikt (bookworm voor Debian 12)
        $output[] = "d-i mirror/suite string bookworm";
        // Voeg eventueel updates en security toe
        $output[] = "d-i apt-setup/use_mirror boolean true";
        $output[] = "d-i apt-setup/restricted boolean true";
        $output[] = "d-i apt-setup/multiverse boolean true";
        $output[] = "d-i apt-setup/services-select multiselect security, updates";
        $output[] = "d-i apt-setup/security_host string security.debian.org"; // Of je lokale mirror
        $output[] = "d-i apt-setup/security_path string /debian-security";

        if (!empty($data['mirror_proxy'])) {
            $output[] = "d-i mirror/http/proxy string {$data['mirror_proxy']}"; // Let op: alleen http proxy hier
        } else {
            $output[] = "d-i mirror/http/proxy string";
        }
        $output[] = "";

        // --- Gebruikers ---
        $output[] = "# --- Account Setup ---";
        $isRootLoginDisabled = $data['cis_compliant'] || $data['disable_root_login'];

        if ($isRootLoginDisabled) {
            $output[] = "# Root login disabled (CIS or explicit)";
            $output[] = "d-i passwd/root-login boolean false";
            // Zorg ervoor dat er geen wachtwoord wordt gevraagd/gezet
            $output[] = "d-i passwd/root-password password";
            $output[] = "d-i passwd/root-password-again password";
            $output[] = "d-i passwd/root-password-crypted password !"; // Vergrendel account
        } else {
            $output[] = "# Root login enabled";
            $output[] = "d-i passwd/root-login boolean true";
            if (!empty($data['root_password'])) {
                $root_hash = $this->generatePasswordHash($data['root_password']);
                $output[] = "# Root password set (using hash)";
                $output[] = "d-i passwd/root-password-crypted password {$root_hash}";
            } else {
                 $output[] = "# Root password left empty (account enabled but locked)";
                 $output[] = "d-i passwd/root-password password";
                 $output[] = "d-i passwd/root-password-again password";
                 $output[] = "d-i passwd/root-password-crypted password !"; // Vergrendel account
            }
        }

        if ($data['create_user']) {
            $output[] = "# Create standard user";
            $output[] = "d-i passwd/make-user boolean true";
            $output[] = "d-i passwd/user-fullname string {$data['user_fullname']}";
            $output[] = "d-i passwd/username string {$data['username']}";
            if (!empty($data['user_password'])) {
                $user_hash = $this->generatePasswordHash($data['user_password']);
                $output[] = "# Standard user password set (using hash)";
                $output[] = "d-i passwd/user-password-crypted password {$user_hash}";
            } else {
                 $output[] = "# Standard user password left empty (account may be locked)";
                 $output[] = "d-i passwd/user-password password";
                 $output[] = "d-i passwd/user-password-again password";
                 $output[] = "d-i passwd/user-password-crypted password !"; // Vergrendel account
            }
            // Geef gebruiker sudo rechten als root login uit staat
            if ($isRootLoginDisabled) {
                 $output[] = "# Grant sudo rights to standard user";
                 $output[] = "d-i passwd/user-default-groups string adm sudo"; // adm is vaak ook nuttig
            } else {
                 $output[] = "d-i passwd/user-default-groups string"; // Geen extra groepen standaard
            }
        } else {
             $output[] = "# Do not create standard user";
             $output[] = "d-i passwd/make-user boolean false";
             if ($isRootLoginDisabled) {
                 $output[] = "# WARNING: Root login disabled and no standard user created!";
                 $output[] = "#          System might be inaccessible after install.";
             }
        }
        $output[] = "";

        // --- Partitionering ---
        $output[] = "# --- Disk Partitioning ---";
        // Gebruik de schijf waar GRUB op komt als target voor auto-partitioning
        $targetDisk = ($data['grub_install_device'] !== 'all') ? $data['grub_install_device'] : '/dev/sda'; // Fallback naar sda als 'all'
        $output[] = "d-i partman-auto/disk string {$targetDisk}";

        if ($data['cis_compliant']) {
            $output[] = "# CIS Compliant Partitioning (using expert recipe)";
            $output[] = "d-i partman-auto/method string regular"; // Basis methode, recept overschrijft
            $output[] = "d-i partman-auto/expert_recipe_file string /cdrom/cis-partitioning.rcp"; // Verwacht een recept op de ISO/netboot
            // OF definieer het recept direct hier (wordt erg lang en complex)
            // $output[] = "d-i partman-auto/expert_recipe string \\";
            // $output[] = "  cis_scheme :: \\";
            // $output[] = "    512 512 1024 ext4 \\";
            // $output[] = "      $primary{ } $bootable{ } method{ format } format{ } \\";
            // $output[] = "      use_filesystem{ } filesystem{ ext4 } mountpoint{ /boot } . \\";
            // $output[] = "    10000 10000 10000 ext4 \\";
            // $output[] = "      method{ format } format{ } use_filesystem{ } filesystem{ ext4 } \\";
            // $output[] = "      mountpoint{ / } options/noatime{ noatime } . \\";
            // $output[] = "    # ... VEEL MEER REGELS VOOR /home, /tmp, /var, /var/log, /var/log/audit, swap ... \\";
            // $output[] = "    # ... met opties zoals nodev, nosuid, noexec ... \\";
            // $output[] = "    1024 1024 2048 linux-swap \\";
            // $output[] = "      method{ swap } format{ } .";
            // $output[] = "";
            $output[] = "# NOTE: The file /cdrom/cis-partitioning.rcp must exist for this to work.";
            $output[] = "#       Generating this recipe dynamically here is very complex.";

        } else {
            $output[] = "# Standard Guided Partitioning";
            $output[] = "d-i partman-auto/method string {$data['partitioning_method']}"; // regular, lvm, crypto

            if ($data['partitioning_method'] === 'regular') {
                $recipe = $data['separate_home'] ? 'multi' : 'atomic';
                $output[] = "d-i partman-auto/choose_recipe select {$recipe}";
            } elseif ($data['partitioning_method'] === 'lvm') {
                $output[] = "d-i partman-auto-lvm/guided_size string max";
                $output[] = "d-i partman-auto/choose_recipe select atomic"; // Of multi binnen LVM
            } elseif ($data['partitioning_method'] === 'crypto') {
                $output[] = "d-i partman-auto-crypto/guided_size string max";
                // !! ZEER ONVEILIG !! Vervang door prompt of veilige methode in productie
                $output[] = "d-i partman-crypto/passphrase password secret";
                $output[] = "d-i partman-crypto/passphrase-again password secret";
                $output[] = "d-i partman-auto/choose_recipe select atomic";
            }
        }

        // Bevestigingen (altijd nodig)
        $output[] = "d-i partman-partitioning/confirm_write_new_label boolean true";
        $output[] = "d-i partman/choose_partition select finish";
        $output[] = "d-i partman/confirm boolean true";
        $output[] = "d-i partman/confirm_nooverwrite boolean true";
        // Voor LVM/Crypto kan extra bevestiging nodig zijn
        if (!$data['cis_compliant']) {
            // Check if the key exists before accessing it
            $method = $data['partitioning_method'] ?? null; // Use null coalescing operator

            if ($method === 'lvm' || $method === 'crypto') {
                $output[] = "d-i partman-lvm/confirm boolean true";
                $output[] = "d-i partman-lvm/confirm_nooverwrite boolean true";
            }
            if ($method === 'crypto') {
                $output[] = "d-i partman-crypto/confirm_erase boolean true";
            }
        }
        $output[] = "";

        // --- Software Selectie ---
        $output[] = "# --- Software Selection ---";
        $tasksString = implode(', ', $data['tasks']);
        $output[] = "tasksel tasksel/first multiselect {$tasksString}";
        // Zorg dat 'standard' altijd aanwezig is?
        if (!in_array('standard', $data['tasks'])) {
             $output[] = "# Ensuring 'standard' task is selected";
             $output[] = "tasksel tasksel/include string standard";
        }

        if (!empty($data['additional_packages'])) {
            $output[] = "d-i pkgsel/include string {$data['additional_packages']}";
        }
        $output[] = "d-i pkgsel/upgrade select full-upgrade";
        $output[] = "d-i pkgsel/update-policy select unattended-upgrades"; // Configureer unattended upgrades
        $output[] = "popularity-contest popularity-contest/participate boolean false";
        $output[] = "";

        // --- Tijdzone ---
        $output[] = "# --- Timezone ---";
        $output[] = "d-i clock-setup/utc boolean true";
        $output[] = "d-i time/zone string {$data['timezone']}";
        $output[] = "d-i clock-setup/ntp boolean true";
        // Optioneel: specifieke NTP server
        // $output[] = "d-i clock-setup/ntp-server string ntp.example.com";
        $output[] = "";

        // --- Bootloader ---
        $output[] = "# --- Bootloader Installation ---";
        $output[] = "d-i grub-installer/grub2_instead_of_grub_legacy boolean true";
        if (strtolower($data['grub_install_device']) === 'all') {
             $output[] = "# Install GRUB to all available devices (MBR/EFI)";
             $output[] = "d-i grub-installer/only_debian boolean true";
             $output[] = "d-i grub-installer/with_other_os boolean true"; // Detecteer andere OS'en
             $output[] = "d-i grub-installer/bootdev string default";
        } else {
            $output[] = "# Install GRUB to specific device: {$data['grub_install_device']}";
            $output[] = "d-i grub-installer/bootdev string {$data['grub_install_device']}";
        }
        // Voorkom prompt als er al een andere GRUB is
        $output[] = "grub-pc grub-pc/install_devices_failed boolean false";
        $output[] = "grub-pc grub-pc/install_devices_empty boolean false";
        $output[] = "";

        // --- Late Command ---
        $output[] = "# --- Late Command Execution ---";
        $lateCommand = trim($data['late_command'] ?? '');

        if ($data['cis_compliant']) {
            $cisCommands = trim($this->generateCisLateCommands($data));
            if (!empty($cisCommands)) {
                // Voeg CIS commando's toe aan het begin
                $lateCommand = $cisCommands . "\n" . $lateCommand;
            }
        }

        $lateCommand = trim($lateCommand); // Trim opnieuw na eventuele toevoeging

        if (!empty($lateCommand)) {
             // Vervang enkele quotes binnen het commando om problemen te voorkomen
             $escapedCommand = str_replace("'", "'\\''", $lateCommand);
             $output[] = "# Running late commands in target system";
             $output[] = "d-i preseed/late_command string \\";
             $output[] = "  in-target sh -c '" . $escapedCommand . "'";
             // Alternatief: download script
             // $output[] = "d-i preseed/late_command string wget http://.../script.sh -O /target/tmp/script.sh ; chmod +x /target/tmp/script.sh ; in-target /tmp/script.sh";
        } else {
            $output[] = "# No late commands specified.";
        }
        $output[] = "";

        // --- Afronding ---
        $output[] = "# --- Finishing Installation ---";
        $output[] = "d-i finish-install/reboot_in_progress note";
        $output[] = "# d-i cdrom-detect/eject boolean true"; // Optioneel: eject CD/DVD
        $output[] = "";
        $output[] = "# =======================================================";
        $output[] = "# End of Preseed Configuration";
        $output[] = "# =======================================================";


        return implode("\n", $output);
    }

     /**
     * Genereert een lijst met basis CIS hardening commando's voor late_command.
     * DIT IS EEN ZEER VEREENVOUDIGD VOORBEELD! Echte CIS compliance is veel complexer.
     *
     * @param array $data Gevalideerde formulier data (kan gebruikt worden voor context)
     * @return string Multi-line string met shell commando's
     */
    private function generateCisLateCommands(array $data): string
    {
        $commands = [];
        $commands[] = "# --- Basic CIS Hardening Commands (late_command) ---";
        $commands[] = "echo '*** Applying Basic CIS Hardening via late_command ***' >&2";

        // 1. Filesystem Mount Options (via fstab - partman is beter maar complex)
        $commands[] = "echo 'Remounting filesystems with CIS options (best effort)' >&2";
        $commands[] = "mount -o remount,nodev /tmp || echo 'Failed to remount /tmp with nodev'";
        $commands[] = "mount -o remount,nosuid /tmp || echo 'Failed to remount /tmp with nosuid'";
        $commands[] = "mount -o remount,noexec /tmp || echo 'Failed to remount /tmp with noexec'";
        // Pas fstab aan (riskant, kan boot problemen geven als partities niet bestaan)
        $commands[] = "sed -i -E '/\\s+\\/tmp\\s+/ s/(defaults[[:alnum:],]*)/\\1,nodev,nosuid,noexec/' /etc/fstab";
        // Herhaal voor /var/tmp, /home, /dev/shm etc. (vereist dat die partities bestaan!)

        // 2. Configure Software Updates (unattended-upgrades)
        $commands[] = "echo 'Configuring unattended-upgrades' >&2";
        $commands[] = "apt-get update";
        $commands[] = "apt-get install -y unattended-upgrades apt-listchanges";
        $commands[] = "dpkg-reconfigure -plow unattended-upgrades"; // Basis configuratie

        // 3. Secure Boot Settings (permissions on grub.cfg)
        $commands[] = "echo 'Securing GRUB configuration' >&2";
        $commands[] = "chown root:root /boot/grub/grub.cfg";
        $commands[] = "chmod og-rwx /boot/grub/grub.cfg";

        // 4. Auditing (install auditd - regels zijn complex)
        $commands[] = "echo 'Installing and enabling auditd' >&2";
        $commands[] = "apt-get install -y auditd audispd-plugins";
        // Voeg hier audit regels toe aan /etc/audit/rules.d/
        $commands[] = "echo '-w /etc/sudoers -p wa -k scope' >> /etc/audit/rules.d/50-cis-audit.rules";
        $commands[] = "echo '-w /etc/sudoers.d/ -p wa -k scope' >> /etc/audit/rules.d/50-cis-audit.rules";
        // ... veel meer regels ...
        $commands[] = "systemctl enable auditd";
        // Starten gebeurt meestal automatisch na enable/reboot

        // 5. Warning Banners
        $commands[] = "echo 'Setting login banners' >&2";
        $banner_text="Authorized uses only. All activity may be monitored and reported.";
        $commands[] = "echo \"${banner_text}\" > /etc/issue";
        $commands[] = "echo \"${banner_text}\" > /etc/issue.net";
        $commands[] = "rm -f /etc/motd"; // Verwijder motd of pas aan

        // 6. Remove unnecessary packages (Voorbeeld)
        $commands[] = "echo 'Removing unnecessary packages' >&2";
        $commands[] = "apt-get purge -y xinetd telnetd nis rsh-client rsh-redone-client";

        // 7. Firewall (ufw)
        if (in_array('ssh-server', $data['tasks'])) {
            $commands[] = "echo 'Configuring UFW firewall' >&2";
            $commands[] = "apt-get install -y ufw";
            $commands[] = "ufw default deny incoming";
            $commands[] = "ufw default allow outgoing";
            $commands[] = "ufw allow ssh"; // Sta SSH toe
            // Voeg eventueel andere benodigde poorten toe (bv. http/https als web-server is gekozen)
            if (in_array('web-server', $data['tasks'])) {
                 $commands[] = "ufw allow http";
                 $commands[] = "ufw allow https";
            }
            $commands[] = "echo 'y' | ufw enable"; // Forceer enable zonder interactieve prompt
        }

        // 8. SSH Hardening (Basis voorbeeld)
        if (in_array('ssh-server', $data['tasks'])) {
            $commands[] = "echo 'Applying basic SSH hardening' >&2";
            $commands[] = "sed -i '/^#*PermitRootLogin/c\\PermitRootLogin no' /etc/ssh/sshd_config";
            $commands[] = "sed -i '/^#*PasswordAuthentication/c\\PasswordAuthentication no' /etc/ssh/sshd_config"; // Forceer key-based auth
            $commands[] = "sed -i '/^#*ChallengeResponseAuthentication/c\\ChallengeResponseAuthentication no' /etc/ssh/sshd_config";
            $commands[] = "sed -i '/^#*UsePAM/c\\UsePAM yes' /etc/ssh/sshd_config"; // Zorg dat PAM gebruikt wordt
            $commands[] = "systemctl restart sshd";
        }

        $commands[] = "echo '*** Basic CIS Hardening via late_command finished ***' >&2";
        $commands[] = "# --- End Basic CIS Hardening Commands ---";

        return implode("\n", $commands);
    }

    /**
     * Genereert een wachtwoord hash (SHA-512) voor preseed.
     *
     * @param string|null $password
     * @return string|null Geeft '!' terug als wachtwoord leeg is (locked account)
     */
    private function generatePasswordHash(?string $password): ?string
    {
        if (empty($password)) {
            return '!'; // Speciale waarde voor een vergrendeld account in preseed
        }
        // Gebruik crypt() met SHA-512 (id=6)
        $salt = '$6$' . Str::random(16) . '$'; // Genereer een random salt
        return crypt($password, $salt);
    }    

}
