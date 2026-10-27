// Run from the reference Go module: go run /path/to/tests/Interop/main.go input.json output.json fixture-dir
package main

import (
	"bytes"
	"encoding/base64"
	"encoding/json"
	"fmt"
	sdk "github.com/waas-api/api-sdk-go/crypto"
	"os"
)

func must(err error) {
	if err != nil {
		panic(err)
	}
}
func read(path string, target any) {
	b, e := os.ReadFile(path)
	must(e)
	must(json.Unmarshal(b, target))
}
func main() {
	var in map[string]string
	var f map[string]string
	var r map[string]any
	read(os.Args[1], &in)
	read(os.Args[3]+"/codevasp.json", &f)
	read(os.Args[3]+"/signature_v2_fixture.json", &r)
	message, e := base64.StdEncoding.DecodeString(in["message"])
	must(e)
	sealed, e := base64.StdEncoding.DecodeString(in["ciphertext"])
	must(e)
	receiver, e := sdk.NewCipher(f["fixtureReceiverSeedB64"], f["fixturePubKeyB64"])
	must(e)
	plain, e := receiver.Decrypt(sealed)
	must(e)
	if !bytes.Equal(plain, message) {
		panic("PHP -> Go plaintext mismatch")
	}
	must(sdk.VerifyCodeVasp(message, in["ed_signature"], f["fixturePubKeyB64"]))
	public, e := sdk.ParseRsaPublicKey(r["public_key_pem"].(string))
	must(e)
	must(sdk.VerifyV2WithKey(in["message"], sdk.AlgRsaSha256, public, in["rsa_signature"]))
	// Fresh reverse direction: Go sender -> PHP receiver.
	sender, e := sdk.NewCipher(f["fixtureReceiverSeedB64"], f["fixturePubKeyB64"])
	must(e)
	cipher, e := sender.Encrypt(message)
	must(e)
	ed, e := sdk.SignCodeVasp(message, f["fixtureReceiverSeedB64"])
	must(e)
	private, e := sdk.ParseRsaPrivateKey(r["private_key_pem"].(string))
	must(e)
	rsa, e := sdk.SignV2WithKey(in["message"], sdk.AlgRsaSha256, private)
	must(e)
	out, e := json.Marshal(map[string]string{"message": in["message"], "ciphertext": base64.StdEncoding.EncodeToString(cipher), "ed_signature": ed, "rsa_signature": rsa})
	must(e)
	must(os.WriteFile(os.Args[2], out, 0600))
	fmt.Println("PASS PHP -> Go RSA, Ed25519 and NaCl box")
}
