# UPDATING.md

This document describes the process for updating **BetterAltay** to support a new Minecraft Bedrock release. Minecraft's network protocol changes frequently, so keeping the server compatible requires careful inspection and testing.

---

## 1. Checking protocol changes

Start by reviewing the official protocol documentation and pick the branch for the target game version:

* [Mojang/bedrock-protocol-docs](https://github.com/Mojang/bedrock-protocol-docs)

Because the official docs can sometimes be incomplete or contain mistakes, cross-check with additional sources:

* [Kaooot/bedrock-protocol-docs](https://github.com/Kaooot/bedrock-protocol-docs)
* [pmmp/BedrockProtocol](https://github.com/pmmp/BedrockProtocol)

Compare these repositories to determine which packets or fields were added, removed or modified.

---

## 2. Updating ProtocolInfo

Usually the file

```
BetterAltay/src/pocketmine/network/mcpe/protocol/ProtocolInfo.php
```

needs updating when the protocol changes.

* Bump the `CURRENT_PROTOCOL` constant to the new protocol version.
* Update `MINECRAFT_VERSION_NETWORK` to the correct game version string.

---

## 3. Adding or updating packets

* New packet classes can be added under `src/pocketmine/network/mcpe/protocol/` for packets introduced in the latest protocol. At a minimum, implement the new packets that are necessary for gameplay and client joinability. You are free to add others.
* For modified packets, update `encodePayload()` and `decodePayload()` so the encoding/decoding matches the new spec.
* Also update any other parts of the code that use these packets, to match the changes
* Adjust constants to fit the new version.
* Remove packets that no longer exist in the protocol to avoid confusion.

---

## 4. Updating game data

When Mojang introduces new content, several server-side data files must often be refreshed. The most important ones include:

* **`runtime_item_states.json`** – Mainly contains item runtime IDs.

  Sources:

  * [Kaooot/bedrock-network-data – item\_palette.json](https://github.com/Kaooot/bedrock-network-data/blob/master/preview/1.21.110.26/item_palette.json) (renamed to `runtime_item_states.json` and sorted by [sort_item_palette.py](https://gist.github.com/Benedikt05/73e9970ba18b9d46cf9fbcf261f5448d))
  * [CloudburstMC/Data](https://github.com/CloudburstMC/Data) or generated via ProxyPass – runtime_item_states.json (needs slight adjustments for BetterAltay)

* **`canonical_block_states.nbt`** – Can be taken from [pmmp/BedrockData](https://github.com/pmmp/BedrockData) or generated from `block_palette.nbt` found in [Kaooot/bedrock-network-data](https://github.com/Kaooot/bedrock-network-data/) or [CloudburstMC/Data](https://github.com/CloudburstMC/Data), using [this script](https://gist.github.com/DavyCraft648/942e8cf8534d3e48ea990aa4503b59f1).

* **`r12_to_current_block_map.bin`** – Available directly from [pmmp/BedrockData](https://github.com/pmmp/BedrockData).

* **`r16_to_current_item_map.json`** – Either updated manually or taken from [pmmp/BedrockData](https://github.com/pmmp/BedrockData).

* **`stripped_biome_definitions.json`** – Obtainable from [CloudburstMC/Data](https://github.com/CloudburstMC/Data) or generated via ProxyPass.

* **`creative_items.json`** – Can be pulled from [Kaooot/bedrock-network-data](https://github.com/Kaooot/bedrock-network-data/).

---

## 5. Testing the update

Test against the latest Bedrock client version:

1. Start a BetterAltay server from your updated branch.
2. Join with a stable client matching the target protocol.
3. Test block breaking and whatever you need to playtest.

If the client disconnects or crashes, you will need to investigate packet mismatches or unexpected behavior to identify the issue.

---

## 6. Final notes

* Expect surprises: Mojang sometimes changes the protocol without increasing the protocol version.
* While most updates are straightforward and can be handled using these instructions, some may require advanced protocol knowledge and the guide alone might not be sufficient.

---

##
